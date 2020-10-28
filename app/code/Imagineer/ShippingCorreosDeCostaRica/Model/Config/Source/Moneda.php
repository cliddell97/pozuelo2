<?php
namespace Imagineer\ShippingCorreosDeCostaRica\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Moneda implements ArrayInterface
{
 public function __construct()
 {

 }
 public function toOptionArray()
 {
  return [
    ['value' => 'CRC', 'label' => __('Colones (CRC)')],
    ['value' => 'USD', 'label' => __('Dólares (USD)')]
  ];
 }
}
