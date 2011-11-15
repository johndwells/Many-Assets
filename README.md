#Many Assets

* Author: [John D Wells](http://johndwells.com)

## Version 1.0.0 

* Requires: [ExpressionEngine 2](http://expressionengine.com/)
* [Playa](http://pixelandtonic.com/playa)

## Description

A simple plugin that allows you to retrieve Assets attached to more than one entry. Simply pass a pipe- or comma-delimited list of entry ids, add parse="inward", and be on your way.

## Installation

1. Copy the many_assets folder to ./system/expressionengine/third_party/

## Required Parameters

Accepts all of Asset's documented parameters, plus:

### entry_ids

A pipe- or string-delimited list of entry IDs.

### parse="inward"

This is required for the plugin to parse in the correct order.

## Example

	{exp:many_assets entry_ids="420,1976,2011" parse="inward"}
	...
	{/exp:many_assets}

