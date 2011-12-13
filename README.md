# Many Assets

* Author: [John D Wells](http://johndwells.com)
* Version 1.3.0 (consider beta until I say otherwise)

Requirements:

* PHP5
* [ExpressionEngine](http://expressionengine.com/) 2.1.3 or later
* [P&Ts Assets](http://pixelandtonic.com/assets) 1.1 or later


## Description

A simple plugin that allows you to retrieve [Pixel&Tonic's Assets](http://pixelandtonic.com/assets) from across many Entries, and/or across many custom fields.

It also allows you to easily retrieve Assets from outside the exp:channel:entries tag.

Simply pass a pipe- or comma-delimited list of entry ids, add parse="inward", and be on your way. 

_Aren't you glad I didn't call it 'ManEE Assets'?_


## Installation

1. Be sure Assets is installed and configured to your liking
2. Copy the many_assets folder to ./system/expressionengine/third_party/


## Required Parameters

**`entry_ids="..."`**

A pipe- or comma-delimited list of entry IDs.

**`parse="inward"`**

*This is required for the plugin to parse in the correct order.*


## Optional Parameters

Accepts all of Asset's [documented parameters](http://pixelandtonic.com/assets/docs/templates), plus:

**`fields="..."`**

A pipe- or comma-delimited list of fields that the lookup should be limited to. Prefix with "not " to exclude fields.

The format of each field is: `channel_name:field_name`, and in the case of a Matrix column, `channel_name:field_name:column_name`.



## Usage Examples #

### Basic #

	{exp:many_assets entry_ids="420|1976|2011" parse="inward"}
		...
	{/exp:many_assets}


### Limit to field name & matrix col #

	{exp:many_assets entry_ids="420|1976|2011" fields="page:cf_page_slideshow|page:cf_page_matrix:file" parse="inward"}
		...
	{/exp:many_assets}


### Combined with Playa and CE Img #

	{exp:many_assets entry_ids="{exp:playa:parent_ids entry_id='420'}" parse="inward" prefix="asset" limit="5" orderby="random"}

		{exp:ce_img:pair src="{asset:server_path}" width="450" height="320" crop="yes" allow_scale_larger="yes"}
			<figure>
				<img src="{made}" width="{width}" height="{height}" alt="" />
				{if asset:caption}<figcaption>{asset:caption}</figcaption>{/if}
			</figure>
		{/exp:ce_img:pair}
		
	{/exp:many_assets}
