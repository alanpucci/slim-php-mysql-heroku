<?php
interface IApiUsable
{
	public function CargarUno($request, $response, $args);
	public function TraerTodos($request, $response, $args);
}