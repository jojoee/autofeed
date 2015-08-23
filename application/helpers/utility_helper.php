<?php

/*================================================================
	#Constants
	================================================================*/

function get_github_url()          { return GITHUB_URL; }
function get_facebook_url()        { return FACEBOOK_URL; }
function get_twitter_url()         { return TWITTER_URL; }

function get_google_map_key()      { return GOOGLE_MAP_KEY; }
function get_ga_code()             { return GA_CODE; }

function get_facebook_app_id()            { return FB_APP_ID; }
function get_facebook_app_secret()        { return FB_APP_SECRET; }
function get_facebook_user_id()           { return FB_USER_ID; }
function get_facebook_user_access_token() { return FB_USER_ACCESS_TOKEN; }
function get_facebook_page_id()           { return FB_PAGE_ID; }
function get_facebook_page_access_token() { return FB_PAGE_ACCESS_TOEKN; }

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

/*================================================================
	#Utility
	================================================================*/

/**
 * check string is null or empty
 *
 * @link	 http://stackoverflow.com/questions/381265/better-way-to-check-variable-for-null-or-empty-string
 * @param	[string]	$str
 * @return boolean
 */
function is_null_or_empty_string($str = '')
{
	return ( ! isset($str) || trim($str) === '');
}

/**
 * get client ip address
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

/**
 * [get_referrer_link description]
 * @return [type] [description]
 */
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
 * is string start with 'your-string'
 *
 * @see http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
 * 
 * @example startsWith("abcdef", "ab") // true
 * @example startsWith("abcdef", "cd") // false
 * @example startsWith("abcdef", "ef") // false
 * @example startsWith("abcdef", "") // true
 * @example startsWith("", "abcdef") // false
 */
function start_with($haystack, $needle)
{
	// search backwards starting from haystack length characters from the end
	return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}
 
/**
 * is string start with 'your-string'
 *
 * @see http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
 *
 * @example endsWith("abcdef", "ab") // false
 * @example endsWith("abcdef", "cd") // false
 * @example endsWith("abcdef", "ef") // true
 * @example endsWith("abcdef", "") // true
 * @example endsWith("", "abcdef") // false
 */
function end_with($haystack, $needle)
{
	// search forward starting from end minus needle length characters
	return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
}

/**
 * gets the data from a URL
 *
 * @see http://davidwalsh.name/curl-download
 * 
 * @example $arr = 'myvar1=' . $myvar1 . '&myvar2=' . $myvar2;
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
 * [get_current_domain_name description]
 *
 * @see	http://stackoverflow.com/questions/10717249/get-current-domain
 * @see	http://stackoverflow.com/questions/1201194/php-getting-domain-name-from-subdomain
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
 * @return [type] [description]
 */
function get_domain_name($url = '')
{
	if (is_null_or_empty_string($url))
	{
		return $_SERVER['SERVER_NAME'];
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
 * [get_request_url description]
 * 
 * @example /
 * @example /stainless-steel/urinals
 * 
 * @return [type] [description]
 */
function get_request_url($url = '', $domain_name = '')
{	
	$results = "$_SERVER[REQUEST_URI]";

	if ( ! is_null_or_empty_string($domain_name) && ! is_null_or_empty_string($domain_name))
	{
		$banned_str = array(
			'http://www.',
			'https://',
			'http://',
			$domain_name
		);
		$results = str_replace($banned_str, '', remove_trailing_slash($url));
		$results = '/'.ltrim($results, '/');
	}

	return $results;
}

function get_request_uri($uri, $domain_name) { return get_request_url($uri, $domain_name); }



/**
 * [is_url_exists description]
 * @param	[type]	$url [description]
 *
 * HTTP/1.1 200 OK Date: Fri, 27 Feb 2015 07:33:01 GMT Server: Apache/2.2.29 (Unix) mod_ssl/2.2.29 OpenSSL/1.0.1e-fips mod_bwlimited/1.4 X-Powered-By: PHP/5.4.34 Expires: Thu, 19 Nov 1981 08:52:00 GMT Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0 Pragma: no-cache Set-Cookie: PHPSESSID=v6s6ufjgj3h3stk111bmgtt0h0; path=/ Set-Cookie: ci_session=a%3A5%3A%7Bs%3A10%3A%22session_id%22%3Bs%3A32%3A%226381b64c912fa51884ddf7e523bcad9a%22%3Bs%3A10%3A%22ip_address%22%3Bs%3A13%3A%2254.255.143.64%22%3Bs%3A10%3A%22user_agent%22%3Bb%3A0%3Bs%3A13%3A%22last_activity%22%3Bi%3A1425022382%3Bs%3A9%3A%22user_data%22%3Bs%3A0%3A%22%22%3B%7D0048f31a44445bc38328040f5b11fca6; expires=Fri, 27-Feb-2015 09:33:02 GMT; path=/ Vary: User-Agent,Accept-Encoding Expires: Tue, 16 Jun 2025 20:00:00 GMT Content-Type: text/html
 *
 * @see https://css-tricks.com/snippets/php/check-if-website-is-available/
 * @see http://stackoverflow.com/questions/2280394/how-can-i-check-if-a-url-exists-via-php
 * @return boolean			[description]
 */
function is_url_exists($url) {
	$response = get_header_response($url);

	if ($response) return true;

	return false;	
}

function get_header_response($url)
{
	//check, if a valid url is provided
	if( ! filter_var($url, FILTER_VALIDATE_URL))
	{
		return false;
	}

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

function is_url_redirects($url)
{
	$response = get_header_response($url);

	if (strpos($response, 'Location:')) return true;

	return false;	
}

function get_extension($file_name)
{
	$pieces = explode('.', $file_name);
	$extension = array_pop($pieces);

	return $extension;
}

/**
 * [get_full_url description]
 *
 * @see http://stackoverflow.com/questions/6768793/get-the-full-url-in-php
 * @see http://stackoverflow.com/questions/14912943/how-to-print-current-url-path
 * 
 * @return [type] [description]
 */
function get_full_url()
{
	$actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	// $actual_link = 'http://'. $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

	return $actual_link;
}

function remove_trailing_slash($str) { return rtrim($str, '/'); }
















/*================================================================
	#Vendor - Facebook
	================================================================*/




// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK
// UNCHECK


// function is_url_exist( $url ) {

//	 $ch = curl_init( $url );
//	 curl_setopt( $ch, CURLOPT_NOBODY, true );
//	 curl_exec( $ch );
//	 $code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

//	 if ( $code == 200 ) {
//		$status = true;
//	 } else {
//		 $status = false;
//	 }
	
//	 curl_close( $ch );
//	 return $status;
// }















// function is_home() {
//	 $CI = &get_instance();
//	 $page = trim( $CI->uri->segment(1) );

//	 if ( $page == '' ) {
//		 return true;
//	 } else {
//		 return false;
//	 }
// }


function is_home() {
	return is_page( '' );
}

function is_contact_us() {
	return is_page( 'contact-us' );
}

function is_faq() {
	return is_page( 'faq' );
}

function is_page( $slug ) {
	$current_url = get_full_url();
	if ( ( $current_url == ( base_url() . $slug ) ) || ( $current_url == ( base_url() . $slug . '/' ) ) ) {
		return true;
	} else {
		return false;
	}
}


function get_canonical_tag() {
	$url = get_full_url();

	return str_replace( 'm.britex', 'www.britex', $url );
}

function replace_base_url( $str ) {
	$str = str_replace( '#base_url#', base_url(), $str );
	$str = str_replace( '#base_url_index#', base_url(), $str );
	$str = str_replace( 'http://www.britex.com.au/', base_url(), $str );

	return $str;
}

function replace_base_url_with_main_site( $str ) {
	$main_site = 'http://www.britex.com.au/';
	$str = str_replace( '#base_url#', $main_site, $str );
	$str = str_replace( '#base_url_index#', $main_site, $str );

	return $str;
}

function get_product_display_name_two_line( $str ) {
	return get_part_of_string( $str, 26 );
}

function get_part_of_string( $str, $number ) {
	$maximum_length = $number;
	if ( strlen( trim( $str ) ) > $maximum_length ) {
		// $result = substr( $product['display_product_name'], 0, -3 ) . '...';
		$result = substr( $str, 0, $maximum_length - 3 ) . '...';
	} else {
		$result = $str;
	}

	return $result;
}

function get_product_search_desc( $str ) {
	return get_part_of_string( $str, 47 );
}















