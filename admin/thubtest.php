<?php

$request = '<?xml version="1.0" encoding="ISO-8859-1"?>
<REQUEST Version="2.8">
	<Command>GetOrders</Command>
	<UserID>thub@thecheesemart.com</UserID> 
	<Password>letmeinImthubyo!</Password>
	<Status>all</Status>
	<Provider>YAHOO</Provider> 
	<LimitOrderCount>25</LimitOrderCount> 
	<OrderStartNumber>2</OrderStartNumber>
	<NumberOfDays>5</NumberOfDays>
	<DownloadStartDate>2010-02-21 18:13:40</DownloadStartDate>
	<SecurityKey>xyz</SecurityKey>
</REQUEST>';

$request = '<?xml version="1.0" encoding="ISO-8859-1"?>
<REQUEST Version="2.8">
	<Command>UpdateOrdersShippingStatus</Command> 
	<UserID>thub@thecheesemart.com</UserID> 
	<Password>letmeinImthubyo!</Password>
	<SecurityKey>xyz</SecurityKey>
	<Orders>
  		<Order>
			<HostOrderID>13</HostOrderID>
			<LocalOrderID>4122</LocalOrderID>
			<NotifyCustomer>Yes</NotifyCustomer>
			<ShippedOn>12/05/2005</ShippedOn>
			<ShippedVia>UPS</ShippedVia>
			<ServiceUsed>Ground</ServiceUsed>
			<TrackingNumber>Z3121231213243455</TrackingNumber>
		</Order>
		<Order>
			<HostOrderID>34089</HostOrderID>
			<LocalOrderID>4123</LocalOrderID>
			<NotifyCustomer>No</NotifyCustomer>
			<ShippedOn>12/04/2005</ShippedOn>
			<ShippedVia>FEDEX</ShippedVia>
			<ServiceUsed>2nd Day Air</ServiceUsed>
			<TrackingNumber>F334523234234555</TrackingNumber>
  		</Order>
	</Orders>
</REQUEST>';
/*
$request = '<?xml version="1.0" encoding="ISO-8859-1"?>
<REQUEST Version="2.8">
	<Command>UpdateOrdersPaymentStatus</Command>
	<UserID>thub@thecheesemart.com</UserID> 
	<Password>letmeinImthubyo!</Password>
	<SecurityKey>xyz</SecurityKey>
	￼<Orders>
		<Order>
			<HostOrderID>34088</HostOrderID>
			<LocalOrderID>4122</LocalOrderID>
			<PaymentStatus>Cleared</ShippedVia>
			<ClearedOn>12/05/2005</ClearedOn>
		</Order>
		<Order>
			<HostOrderID>34089</HostOrderID>
			<LocalOrderID>4123</LocalOrderID>
			<PaymentStatus>Pending</ShippedVia>
			<ClearedOn />
		</Order>
	</Orders>
</REQUEST>';

$request = '<?xml version="1.0" encoding="UTF-8"?> 
<REQUEST Version="2.8">
	<Command>UpdateInventory</Command>
	<UserID>thub@thecheesemart.com</UserID> 
	<Password>letmeinImthubyo!</Password>
	<SecurityKey>xyz</SecurityKey>
	<AddProducts>0</AddProducts>
	<AddCategory>0</AddCategory>
	<AddManufacturer>0</AddManufacturer>
	<UpdateDescription>0</UpdateDescription>
	<UpdatePrice>1</UpdatePrice>
	<UpdateInventory>1</UpdateInventory>
	<Items>
		<Item>
			<ItemCode>SKU10087</ItemCode>
			<ItemCodeParent>SKU10000</ItemCodeParent>
			<ItemName>No wrinkle shirt</ItemName>
			<ItemDescription>Spring time shirts, no wrinkles, easy wash</ItemDescription>
			<Manufacturer>Gap Inc.</Manufacturer>
			<Category>Clothing/Boys</Category>
			<Price>25.45</Price>
			<SalePrice>20.00</SalePrice>
			<UnitWeight>2</UnitWeight>
			<QuantityInStock>126</QuantityInStock>
			<ItemOption Name="color">red</ItemOption>
			<ItemOption Name="size">XL</ItemOption>
		</Item>
		<Item>
			<ItemCode>CD:05.12.10</ItemCode>
			<ItemCodeParent>SKU10000</ItemCodeParent>
			<ItemName>No wrinkle shirt</ItemName>
			<ItemDescription>Spring time shirts, no wrinkles, easy wash</ItemDescription>
			<Manufacturer>Gap Inc.</Manufacturer>
			<Category>Clothing/Boys</Category>
			<Price>25.45</Price>
			<SalePrice>20.00</SalePrice>
			<UnitWeight>2</UnitWeight>
			<QuantityInStock>19</QuantityInStock>
			<ItemOption Name="color">white</ItemOption>
			<ItemOption Name="size">small</ItemOption>
		</Item>
	</Items>
</REQUEST>';
*/
?>
<form action="thubservice.php" method="post">
	<input type="hidden" name="request" value="<?=htmlentities($request)?>" />
	<input type="submit" value="test thub" />
</form>