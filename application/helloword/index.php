<?php
namespace helloword;

use zeus\web\Controller;

class index extends Controller
{
	public function index()
	{
		echo __CLASS__."::".__FUNCTION__;

		$var = "template var = {".__CLASS__."::".__FUNCTION__."}";
		
		include tpl("index");
	}
}