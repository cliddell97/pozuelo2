<?php
/**
 * Copyright © 2015 Vexsoluciones. All rights reserved.
 */

namespace Vexsoluciones\Checkout\Model\ResourceModel\Departamento;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vexsoluciones\Checkout\Model\Departamento', 'Vexsoluciones\Checkout\Model\ResourceModel\Departamento');
    }
}
