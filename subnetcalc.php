<?php

class IPV6SubnetCalculator
{
	public function testValidAddress($address)
	{
		return (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== FALSE);
	}

	public function unabbreviateAddress($address)
	{
		$unabbv = $address;

		if (strpos($unabbv, "::") !== FALSE)
		{
			$parts = explode(":", $unabbv);

			$cnt = 0;

			for ($i=0; $i < count($parts); $i++)
			{
				if (is_numeric("0x" . $parts[$i]))
					$cnt++;
			}


			$needed = 8 - $cnt;

			$unabbv = str_replace("::", str_repeat(":0000", $needed), $unabbv);
		}

		$parts = explode(":", $unabbv);
		$new   = "";

		for ($i = 0; $i < count($parts); $i++)
		{
			$new .= sprintf("%04s:", $parts[$i]);
		}

		$unabbv = substr($new, 0, -1);

		return $unabbv;
	}

	public function abbreviateAddress($address)
	{
		$abbv = $address;

		if (strpos($abbv, "::") === FALSE)
		{
			$parts  = explode(":", $abbv);
			$nparts = array();

			$ignore = false;
			$done   = false;

			for ($i=0;$i<count($parts);$i++)
			{
				if (intval(hexdec($parts[$i])) === 0 && $ignore == false && $done == false)
				{
					$ignore   = true;
					$nparts[] = '';

					if ($i == 0)
						$nparts[] = '';
				}
				else if (intval(hexdec($parts[$i])) === 0 && $ignore == true && $done == false)
				{
					continue;
				}
				else if (intval(hexdec($parts[$i])) !== 0 && $ignore == true)
				{
					$done   = true;
					$ignore = false;

					$nparts[] = $parts[$i];
				}
				else
				{
					$nparts[] = $parts[$i];
				}

			}
			$abbv = implode(":", $nparts);
		}

		$abbv = preg_replace("/:0{1,3}/", ":", $abbv);

		return $abbv;
	}

	public function getInterfaceCount($prefix_len)
	{
		$actual = pow(2, (128-$prefix_len));

		return number_format($actual);
	}

	public function getAddressRange($address, $prefix_len)
	{
		$unabbv = $this->unabbreviateAddress($address);
		$parts  = explode(":", $unabbv);

		$bstring = str_repeat("1", $prefix_len) . str_repeat("0", 128-$prefix_len);
		$estring = str_repeat("0", $prefix_len) . str_repeat("1", 128-$prefix_len);

		$mins    = str_split($bstring, 16);
		$maxs    = str_split($estring, 16);

		$mb    = "";
		$start = "";
		$end   = "";

		for ($i = 0; $i < 8; $i++)
		{
			$min    = base_convert($mins[$i], 2, 16);
			$max    = base_convert($maxs[$i], 2, 16);

			$mb    .= sprintf("%04s", $min) . ':';

			$start .= dechex(hexdec($parts[$i]) & hexdec($min)) . ':';
			$end   .= dechex(hexdec($parts[$i]) | hexdec($max)) . ':';
		}

		$prefix_address = substr($mb, 0, -1);

		$start = substr($start, 0, -1);
		$start = $this->unabbreviateAddress($start);

		$end = substr($end, 0, -1);
		$end = $this->unabbreviateAddress($end);

		$ret = array(
				'prefix_address' => $prefix_address,
				'start_address'  => $start,
				'end_address'    => $end,
				);


		return $ret;
	}
}
