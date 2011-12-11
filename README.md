# Many Assets

* Author: [John D Wells](http://johndwells.com)
* Version 1.1.1 (consider beta until I say otherwise)

Requires:

* PHP5
* [ExpressionEngine](http://expressionengine.com/) 2.1.3 or later
* [P&Ts Assets](http://pixelandtonic.com/assets)


## Description

A simple plugin that allows you to retrieve [Pixel&Tonic's Assets](http://pixelandtonic.com/assets) from across many Entries, and/or across many custom fields.

Simply pass a pipe- or comma-delimited list of entry ids, add parse="inward", and be on your way. 

Aren't you glad I didn't call it 'ManEE Assets'?


## Installation

1. Be sure Assets is installed and configured to your liking
2. Copy the many_assets folder to ./system/expressionengine/third_party/


## Required Parameters

**`entry_ids="..."`**

A pipe- or string-delimited list of entry IDs.

**`parse="inward"`**

This is required for the plugin to parse in the correct order.


## Optional Parameters

Accepts all of Asset's documented parameters, plus:

**`field_names="..."`**

A pipe- or string-delimited list of field names that the lookup should be limited to.

*beta*: To limit to specific matrix columns, format should be "field_name:column_name"


## Usage Examples #

### Basic #

	{exp:many_assets entry_ids="420,1976,2011" parse="inward"}
		...
	{/exp:many_assets}


### Limit to field name & matrix col #

	{exp:many_assets entry_ids="420,1976,2011" field_names="cf_page_slideshow,cf_page_matrix:file" parse="inward"}
		...
	{/exp:many_assets}


### Combined with Playa and CE Img #

	{exp:many_assets entry_ids="{exp:playa:parent_ids entry_id='420' delimiter=','}" parse="inward" prefix="asset" limit="5" orderby="random"}

		{exp:ce_img:pair src="{asset:server_path}" width="450" height="320" crop="yes" allow_scale_larger="yes"}
			<figure>
				<img src="{made}" width="{width}" height="{height}" alt="" />
				{if asset:caption}<figcaption>{asset:caption}</figcaption>{/if}
			</figure>
		{/exp:ce_img:pair}
		
	{/exp:many_assets}


