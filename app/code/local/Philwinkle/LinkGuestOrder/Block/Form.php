<?php

class Philwinkle_LinkGuestOrder_Block_Form extends Mage_Sales_Block_Widget_Guest_Form
{
	public function getActionUrl()
	{
		return $this->getUrl('*/*/view');
	}
}