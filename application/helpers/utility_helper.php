<?php

/*================================================================
	#Constants
	================================================================*/

function get_github_url() { return GITHUB_URL; }
function get_facebook_url() { return FACEBOOK_URL; }
function get_twitter_url() { return TWITTER_URL; }

function get_google_map_key() { return GOOGLE_MAP_KEY; }
function get_ga_code() { return GA_CODE; }

/*================================================================
	#Debug
	================================================================*/

function dd($var, $die = true)
{
	echo '<pre>';
	print_r($var);
	echo '</pre>';
	if ($die) die();
}

function da($var) { dd($var, false); }

/*================================================================
	#Utility
	================================================================*/

/**
 * check string is null or empty
 * TESTED
 * 
 * @link http://stackoverflow.com/questions/381265/better-way-to-check-variable-for-null-or-empty-string
 * 
 * @param	 [string]	$str
 * @return [boolean]
 */
function is_null_or_empty_string($str = '')
{
	return ( ! isset($str) || trim($str) === '');
}

/**
 * Get client ip address
 * 
 * @return [string] ip of client
 */
function get_ip_addr()
{
	if ( ! empty($_SERVER['HTTP_CLIENT_IP']))
	{
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	}
	elseif ( ! empty($_SERVER['HTTP_X_FORWARDED_FOR']))
	{
		//to check ip is pass from proxy
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	else
	{
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	return $ip;
}

function get_referrer_link()
{
	$refer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	if ($refer)
	{
		return $refer; 
	}
	else
	{
		return $_SERVER['PHP_SELF']; // if not referre than return the curren page
	}
}

/**
 * Is string start with 'your-string' ?
 * TESTED
 * 
 * @link http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
 * 
 * @example start_with("abcdef", "ab") // true
 * @example start_with("abcdef", "cd") // false
 * @example start_with("abcdef", "ef") // false
 * @example start_with("abcdef", "") // true
 * @example start_with("", "abcdef") // false
 */
function start_with($haystack, $needle)
{
	// search backwards starting from haystack length characters from the end
	return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}
 
/**
 * Is string end with 'your-string' ?
 * TESTED
 *
 * @link http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
 *
 * @example end_with("abcdef", "ab") // false
 * @example end_with("abcdef", "cd") // false
 * @example end_with("abcdef", "ef") // true
 * @example end_with("abcdef", "") // true
 * @example end_with("", "abcdef") // false
 */
function end_with($haystack, $needle)
{
	// search forward starting from end minus needle length characters
	return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
}

/**
 * Gets the data from a URL
 *
 * @link http://davidwalsh.name/curl-download
 */
function get_url_data($url, $post_datas = '')
{

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$timeout = 5;
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

	if ( ! is_null_or_empty_string($post_datas)) curl_setopt($ch, CURLOPT_POSTFIELDS, $post_datas);

	$data = curl_exec($ch);

	curl_close($ch);

	return $data;
}

/**
 * Get domain name only (no sub domain including)
 * TESTED
 *
 * @link http://stackoverflow.com/questions/10717249/get-current-domain
 * @link http://stackoverflow.com/questions/1201194/php-getting-domain-name-from-subdomain
 *
 * @example get_domain_name() // current domain name
 * @example http://somedomain.co.uk // somedomain.co.uk
 * @example http://www2.manager.co.th
 * @example http://test.manager.co.th
 * @example http://manager.co.th
 * @example http://thaiware.com
 * @example http://www.thaiware.com
 * @example http://test.thaiware.com
 * @example http://www.studentloan.ktb.co.th/
 * @example http://www.studentloan.ktb.co.th/dasdasdasd.html
 * @example http://www.studentloan.ktb.co.th?quewadsas=2faddasdas
 * @example http://www.studentloan.ktb.co.th/2011/20/01?=asdasdasdasd
 * 
 * @return [string]
 */
function get_domain_name($url = '')
{
	if (is_null_or_empty_string($url))
	{
		return get_full_domain_name();
	}
	else
	{
		$pieces = parse_url($url);
		$domain = isset($pieces['host']) ? $pieces['host'] : '';
		if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs))
		{
			return $regs['domain'];
		}
		return false;
	}
}

/**
 * Get request URL
 * TESTED
 * 
 * @example /
 * @example /stainless-steel/urinals
 * 
 * @param  [string] $uri
 * @param  [string] $full_domain_name domain name including sub domain
 * @return [string]
 */
function get_request_url($url = '', $full_domain_name = '')
{	
	$results = "$_SERVER[REQUEST_URI]";

	if ( ! is_null_or_empty_string($full_domain_name) && ! is_null_or_empty_string($full_domain_name))
	{
		$banned_str = array(
			'https://',
			'http://',
			$full_domain_name
		);
		$results = str_replace($banned_str, '', remove_trailing_slash($url));
		$results = '/'.ltrim($results, '/');
	}

	return $results;
}

function get_request_uri($uri, $full_domain_name) { return get_request_url($uri, $full_domain_name); }

/**
 * Get full domain name (domain name including sub domain)
 * TESTED
 * 
 * @param  string $url [description]
 * @return [type]      [description]
 */
function get_full_domain_name($url = '')
{

	if (is_null_or_empty_string($url))
	{
		return $_SERVER['SERVER_NAME'];
	}
	else
	{
		return parse_url($url, PHP_URL_HOST);	
	}
}

/**
 * Check if your url is exists
 * TESTED
 * 
 * @link https://css-tricks.com/snippets/php/check-if-website-is-available/
 * @link http://stackoverflow.com/questions/2280394/how-can-i-check-if-a-url-exists-via-php
 * 
 * @param  [string]	 $url
 * @return [boolean]
 */
function is_url_exists($url)
{
	$response = get_header_response($url);
	if ($response) return true;

	return false;	
}

/**
 * Is URL 404 ?
 * TESTED
 * 
 * @param  [string]  $url
 * @return [boolean]
 */
function is_404($url)
{
	$response = get_header_response($url);
	if (strpos($response, '404 Not Found')) return true;

	return false;	
}

/**
 * Get header response
 * @param  [string] $url
 * @return [string]
 */
function get_header_response($url)
{
	//check, if a valid url is provided
	if( ! filter_var($url, FILTER_VALIDATE_URL)) return false;

	//initialize curl
	$curl_init = curl_init($url);
	
	curl_setopt($curl_init,CURLOPT_CONNECTTIMEOUT,10);
	curl_setopt($curl_init,CURLOPT_HEADER,true);
	curl_setopt($curl_init,CURLOPT_NOBODY,true);
	curl_setopt($curl_init,CURLOPT_RETURNTRANSFER,true);

	//get answer
	$response = curl_exec($curl_init);

	curl_close($curl_init);

	return $response;
}

/**
 * Is URL redirect ?
 * TESTED
 * 
 * @param  [string]  $url
 * @return [boolean]
 */
function is_url_redirects($url)
{
	$response = get_header_response($url);
	if (strpos($response, 'Location:')) return true;

	return false;	
}

/**
 * Get file extension
 * TESTED
 * 
 * @param  [string] $file_name
 * @return [string]
 */
function get_extension($file_name)
{
	$pieces = explode('.', $file_name);
	$extension = array_pop($pieces);

	return $extension;
}

/**
 * Get full URL
 * TESTED
 * 
 * @link http://stackoverflow.com/questions/6768793/get-the-full-url-in-php
 * @link http://stackoverflow.com/questions/14912943/how-to-print-current-url-path
 * 
 * @return [string]
 */
function get_full_url()
{
	$actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

	return $actual_link;
}

/**
 * Remove trailing slash
 * TESTED
 * 
 * @param  [string] $str
 * @return [string]
 */
function remove_trailing_slash($str)
{
	return rtrim($str, '/');
}

/**
 * Get `n` date ago
 * 
 * @param  [number] $n
 * @return [date]
 */
function get_date_ago($n = '30') { return date('Y-m-d', strtotime('-'.$n.' days')); }


/**
 * Get human time difference
 *
 * @link http://stackoverflow.com/questions/2915864/php-how-to-find-the-time-elapsed-since-a-date-time
 * 
 * @param  [datetime] $from
 * @param  [datetime] $to
 * @return [string]
 */
function human_time_diff($from, $to)
{
	$diff_time = $to - $from;

	$tokens = array (
		31536000 => 'year',
		2592000  => 'month',
		604800   => 'week',
		86400    => 'day',
		3600     => 'hour',
		60       => 'minute',
		1        => 'second'
	);

	foreach ($tokens as $unit => $text) {
		
		if ($time < $unit) continue;

		$number_of_units = floor($time / $unit);

		return $number_of_units.' '.$text.(($number_of_units > 1) ? 's':'' );
	}
}
