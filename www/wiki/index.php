<?php
/**
 * This is the main web entry point for MediaWiki.
 *
 * If you are reading this in your web browser, your server is probably
 * not configured correctly to run PHP applications!
 *
 * See the README, INSTALL, and UPGRADE files for basic setup instructions
 * and pointers to the online documentation.
 *
 * http://www.mediawiki.org/
 *
 * ----------
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

# Bail on old versions of PHP.  Pretty much every other file in the codebase
# has structures (try/catch, foo()->bar(), etc etc) which throw parse errors in
# PHP 4. Setup.php and ObjectCache.php have structures invalid in PHP 5.0 and
# 5.1, respectively.
if ( !function_exists( 'version_compare' ) || version_compare( phpversion(), '5.3.2' ) < 0 ) {
	// We need to use dirname( __FILE__ ) here cause __DIR__ is PHP5.3+
	require dirname( __FILE__ ) . '/includes/PHPVersionError.php';
	wfPHPVersionError( 'index.php' );
}

# The Following code is used to generate hdwiki page redirect
# written by chaosconst for ygclub.org
#
function curPageURL() 
{
  $pageURL = 'http://';
  $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
  return $pageURL;
}
$url = curPageURL();
if ((strstr($url,"pic-view")!=false) || (strstr($url,"doc-")!=false) || (strstr($url,"category-")!=false)) {

  $url = str_replace("/wiki/","/hdwiki/",$url);

  header("Location: ".$url);   
  exit;
}
if (strstr($url,"UserLogin")!=false) {
  header("Location: "."http://www.ygclub.org/bbs/member.php?mod=logging&action=login");   
  echo "hello";
  exit;
}


## Initialise common code.  This gives us access to GlobalFunctions, the
# AutoLoader, and the globals $wgRequest, $wgOut, $wgUser, $wgLang and
# $wgContLang, amongst others; it does *not* load $wgTitle
require __DIR__ . '/includes/WebStart.php';

$mediaWiki = new MediaWiki();
$mediaWiki->run();
