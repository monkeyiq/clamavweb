<?php
/******************************************************************************
*******************************************************************************
*******************************************************************************

    Copyright (C) 2021 Ben Martin

    This file is part of clamavweb.

    clamavweb is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 2 of the License, or
    (at your option) any later version.

    clamavweb is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with clamavweb.  If not, see <http://www.gnu.org/licenses/>.

    For more details see the COPYING file in the root directory of this
    distribution.

*******************************************************************************
*******************************************************************************
******************************************************************************/

include 'clamavweb_config.php';


function int_to_network_byte_order( $val )
{
    $a = unpack("I",pack( "i",$val ));
    return pack("N",$a[1] );
}
function bout( $v )
{
    if( $v ) return "1";
    else     return "0";
}


$passes = false;
$error = true;
$iss = fopen('php://input','rb');
$bread = 0;


$fd = fsockopen( $clamhost, $clamport, $errno, $errstr );
if (!$fd) {
    echo "{ \"error\": true, \"reason\": \"$errstr\"}\n";
    return;
}

fwrite($fd, "zINSTREAM\0");
        

// loop through the content of the file, reading and sending $bytes_per_cycle
// per chunk to the daemon
$i=0;
while( true ) {
    $buffer = fread($iss, $bytes_per_cycle);
    $sz = strlen($buffer);
    if( !$buffer ) {
        if( !feof($iss)) {
            echo "{ \"error\": true, \"reason\": \"reading data failed\"}\n";
            return;
        }
        // fread() failed and we are at eof()
        break;
    }
    $bread += $sz;
    if( false === fwrite($fd, int_to_network_byte_order($sz))) {
        echo "{ \"error\": true, \"reason\": \"file might be too big, write cut off\"}\n";
        return;
    }
    $rc=fwrite($fd, $buffer);
    $i++;
}

// done, see what the result is.
fwrite($fd, int_to_network_byte_order(0));
fwrite($fd, "\0");
$reply = fread($fd, 1000);
//echo "have reply2... " . trim($reply) . " \n";

$emsg = "";
if( $reply == "stream: OK\0" ) {
    $passes = true;
    $error = false;
} elseif( preg_match('/^stream:(.*)FOUND\0$/',$reply,$matches)) {
    $passes = false;
    $error = false;
    $emsg = trim($matches[1]);
} else {
    $emsg = trim($reply);
}

echo "{ \"error\": ".bout($error).", \"passes\": ".bout($passes).", \"reason\": \"$emsg\"}\n";





