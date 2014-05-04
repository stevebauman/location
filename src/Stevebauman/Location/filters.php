<?php

Route::filter('location', function($request){
	print_r(Location::getLocation()); die();
});