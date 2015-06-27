#! /usr/bin/php
<?php

include 'blfs-include.php';

$CHAPTER       = '30';
$CHAPTERS      = 'Chapter 30';
$START_PACKAGE = 'accountsservice';
$STOP_PACKAGE  = 'yelp';

$renames = array();
$ignores = array();

$kde_ver  = "";

//$current="polkit-gnome";  // Foir debugging

$regex = array();

$url_fix = array (
   array( 'pkg'     => 'libwacom',
          'match'   => '^.*$', 
          'replace' => "http://sourceforge.net/projects/linuxwacom/files/libwacom/" ),
);

function get_packages( $package, $dirpath )
{
  global $regex;
  global $book_index;
  global $url_fix;
  global $current;
  global $kde_ver;

  if ( isset( $current ) && $book_index != "$current" ) return 0;

  // Fix up directory path
  foreach ( $url_fix as $u )
  {
     $replace = $u[ 'replace' ];
     $match   = $u[ 'match'   ];

     if ( isset( $u[ 'pkg' ] ) )
     {
        if ( $package == $u[ 'pkg' ] )
        {
           $dirpath = preg_replace( "/$match/", "$replace", $dirpath );
           break;
        }
     }
     else
     {
        if ( preg_match( "/$match/", $dirpath ) )
        {
           $dirpath = preg_replace( "/$match/", "$replace", $dirpath );
           break;
        }
     }
  }

  // Check for ftp
  if ( preg_match( "/^ftp/", $dirpath ) ) 
  { 
    if ( $book_index == "polkit-gnome" )
    {
      $dirpath  = rtrim  ( $dirpath, "/" );    // Trim any trailing slash
      $position = strrpos( $dirpath, "/" );
      $dirpath  = substr ( $dirpath, 0, $position );  // Up 1
    }

    if ( $book_index == "libsecret" )
    {
       $dirpath  = rtrim  ( $dirpath, "/" );    // Trim any trailing slash
       $position = strrpos( $dirpath, "/" );
       $dirpath  = substr ( $dirpath, 0, $position );  // Up 1
       $dirs     = http_get_file( "$dirpath/" );
       $dir = find_max( $dirs, "/\d$/", "/^.* ([\d\.]+)$/" );
       $dirpath .= "/$dir/";
    }

    // gsettings-desktop-schemas and similar
    if ( $book_index == "gsettings-desktop-schemas" ||
         $book_index == "libsecret"                 ||
         $book_index == "totem-pl-parser"           ||
         $book_index == "vte"                       ||
         $book_index == "yelp-xsl"                  ||
         $book_index == "GConf"                     ||
         $book_index == "gcr"                       ||
         $book_index == "geocode-glib"              ||
         $book_index == "gjs"                       ||
         $book_index == "gnome-desktop"             ||
         $book_index == "gnome-menus"               ||
         $book_index == "gnome-video-effects"       ||
         $book_index == "grilo"                     ||
         $book_index == "gtkhtml"                   ||
         $book_index == "gtksourceview"             ||
         $book_index == "libchamplain"              ||
         $book_index == "libgee"                    ||
         $book_index == "libgtop"                   ||
         $book_index == "libgweather"               ||
         $book_index == "libpeas"                   ||
         $book_index == "rest"                      ||
         $book_index == "libwnck"                   ||
         $book_index == "gnome-online-accounts"     ||
         $book_index == "libgdata"                  ||
         $book_index == "evolution-data-server"     ||
         $book_index == "caribou"                   ||
         $book_index == "dconf"                     ||
         $book_index == "gnome-backgrounds"         ||
         $book_index == "adwaita-icon-theme"        ||
         $book_index == "gnome-themes-standard"     ||
         $book_index == "gvfs"                      ||
         $book_index == "nautilus"                  ||
         $book_index == "zenity"                    ||
         $book_index == "gnome-bluetooth"           ||
         $book_index == "gnome-keyring"             ||
         $book_index == "gnome-settings-daemon"     ||
         $book_index == "gnome-control-center"      ||
         $book_index == "mutter"                    ||
         $book_index == "gnome-shell"               ||
         $book_index == "gnome-shell-extensions"    ||
         $book_index == "gnome-session"             ||
         $book_index == "gdm"                       ||
         $book_index == "gnome-user-docs"           ||
         $book_index == "yelp"                       )
    {
       $dirpath  = rtrim  ( $dirpath, "/" );    // Trim any trailing slash
       $position = strrpos( $dirpath, "/" );
       $dirpath  = substr ( $dirpath, 0, $position );  // Up 1
       $dirs     = http_get_file( "$dirpath/" );

       if ( $book_index == "gjs"         ||
            $book_index == "gnome-menus" ||
            $book_index == "rest"         )
         $dir = find_max(      $dirs, "/\d$/", "/^.* ([\d\.]+)$/" );
       else
         $dir = find_even_max( $dirs, "/\d$/", "/^.* ([\d\.]+)$/" );
       
       $dirpath .= "/$dir/";
    }

    // Get listing
    $lines = http_get_file( "$dirpath/" );
  }
  else // http
  {
     $lines = http_get_file( $dirpath );
     if ( ! is_array( $lines ) ) return $lines;
  } // End fetch

  if ( isset( $regex[ $package ] ) )
  {
     // Custom search for latest package name
     foreach ( $lines as $l )
     {
        if ( preg_match( '/^\h*$/', $l ) ) continue;
        $ver = preg_replace( $regex[ $package ], "$1", $l );
        if ( $ver == $l ) continue;

        return $ver;  // Return first match of regex
     }

     return 0;  // This is an error
  }

  if ( $book_index == "telepathy-glib"           ||
       $book_index == "telepathy-mission-control" )
     return find_even_max( $lines, "/$package/", "/^.*$package-([\d\.]*\d)\.tar.*$/" );

  // Most packages are in the form $package-n.n.n
  // Occasionally there are dashes (e.g. 201-1)
  $max = find_max( $lines, "/$package/", "/^.*$package-([\d\.]*\d)\.tar.*$/" );
  return $max;
}

function get_pattern( $line )
{
   // Set up specific patter matches for extracting book versions

   $match = array();

   foreach( $match as $m )
   {
      $pkg = $m[ 'pkg' ];

      if ( preg_match( "/$pkg/", $line ) ) 
         return $m[ 'regex' ];
   }

   return "/\D*(\d.*\d)\D*$/";
}

get_current();  // Get what is in the book

// Get latest version for each package 
foreach ( $book as $pkg => $data )
{
   $book_index = $pkg; 

   $base = $data[ 'basename' ];
   $url  = $data[ 'url' ];
   $bver = $data[ 'version' ];

   echo "book index: $book_index $bver $url \n";

   $v = get_packages( $book_index, $url );
   $vers[ $book_index ] = $v;
}

html();  // Write html output
?>
