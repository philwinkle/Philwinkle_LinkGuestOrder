<?php

class Philwinkle_LinkGuestOrder_Block_View extends Mage_Core_Block_Template
{
	public function getOrder()
	{
		return Mage::registry('current_order');
	}
}