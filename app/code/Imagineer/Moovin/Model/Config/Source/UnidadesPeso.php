<?php
namespace Imagineer\Moovin\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class UnidadesPeso implements ArrayInterface
{
 public function __construct()
 {
    
 }
 public function toOptionArray()
 {
  return [
    ['value' => 'gramos', 'label' => __('Gramos')],
    ['value' => 'kilogramos', 'label' => __('Kilogramos')],
    ['value' => 'libras', 'label' => __('Libras')]
  ];
 }
}
