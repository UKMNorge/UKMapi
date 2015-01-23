<?php

/* Compares two strings in constant time, to avoid timing attacks */
function constant_time_compare($str1, $str2) {
	$res = $str1 ^ $str2;
	$ret = strlen($str1) ^ strlen($str2); //not the same length, then fail ($ret != 0)
	for($i = strlen($res) - 1; $i >= 0; $i--) $ret += ord($res[$i]);
	return !$ret;
}
