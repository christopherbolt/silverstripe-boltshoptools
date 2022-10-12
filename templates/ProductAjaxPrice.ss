<% if not $Variation.canPurchase %>
	<% if $Variation.ManageStock && $Variation.StockLevel < 1 %>
        <p class="message">This option has sold out. <a class="btn" href="mailto:$SiteConfig.ContactEmail?subject=$Variation.Product.Title.xml">Enquire</a></p>
    <% else %>
        <p class="message">This product is not available with the options you have chosen. <a class="btn" href="mailto:$SiteConfig.ContactEmail?subject=$Variation.Product.Title.xml">Enquire</a></p>
    <% end_if %>
<% else %>
	<% if $Price < $BasePrice %>
        <span class="price"><span class="basePrice">$BasePrice.Nice</span> <span class="specialPrice">$Price.Nice</span></span>
    <% else %>
        <span class="price">$Price.Nice</span>
    <% end_if %>
<% end_if %>