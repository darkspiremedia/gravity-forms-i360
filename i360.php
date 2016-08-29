<?php
/**
 * Plugin Name: Gravity Forms: i360
 * Plugin URI: 
 * Description: An add-on that connects to the i-360 API
 * Version: 1.0
 * Author: Darkspire Media
 * Author URI: http://darkspire.media
 *
 * ------------------------------------------------------------------------
 * Copyright 2016 Darkspire, Inc.
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
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

define( 'GF_I360_VERSION', '1.0.0' );

add_action( 'gform_loaded', array( 'GF_i360_Bootstrap', 'load' ), 5 );

class GF_i360_Bootstrap {

    public static function load(){

        require_once( 'class-gf-i360.php' );

        GFAddOn::register( 'GFi360' );
    }

}

function gf_i360(){
    return GFi360::get_instance();
}