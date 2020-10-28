<?php
namespace Vexsoluciones\Checkout\Api;
 
interface CheckoutInterface
{
    /**
     * Returns greeting message to user
     *
     * @api
     * @param string $pais Users name.
     * @return string Greeting message with users name.
     */
    public function listardepartamentos($pais);
    /**
     * Returns greeting message to user
     *
     * @api
     * @param string $departamento Users name.
     * @return string Greeting message with users name.
     */
    public function listarprovincias($departamento);
    /**
     * Returns greeting message to user
     *
     * @api
     * @param string $provincia Users name.
     * @return string Greeting message with users name.
     */
    public function listardistritos($provincia);
    /**
     * Returns greeting message to user
     *
     * @api
     * @param string $provincia Users name.
     * @return string Greeting message with users name.
     */
    public function listartiendas();
    /**
     * Returns greeting message to user
     *
     * @api
     * @param string $provincia Users name.
     * @return string Greeting message with users name.
     */
    public function listarpaises();
    
}