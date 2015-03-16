<?php
class Date
{
	public static function isRangeInRange(DateTime $startDate = null, DateTime $endDate = null, DateTime $rangeStart, DateTime $rangeEnd)
	{
		if ($startDate and $startDate > $rangeEnd)
		{
			return false;
		}

		if ($endDate and $endDate < $rangeStart)
		{
			return false;
		}

		return true;
	}
}