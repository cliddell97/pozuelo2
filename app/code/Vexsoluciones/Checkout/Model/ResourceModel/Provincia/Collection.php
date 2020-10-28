<?php
/**
 * Copyright © 2015 Vexsoluciones. All rights reserved.
 */

namespace Vexsoluciones\Checkout\Model\ResourceModel\Provincia;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vexsoluciones\Checkout\Model\Provincia', 'Vexsoluciones\Checkout\Model\ResourceModel\Provincia');
    }
}
