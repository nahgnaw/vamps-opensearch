<?php

include_once("datasets.php");

ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

// type of requested results as defined in the opensearch document. The
// type will be the name of one of the facets, or the name of the result
// set
$type = null;
// number of results to be displayed in the result set
$limit = null;
// offset of the current result set, used by the next and previous
// buttons
$offset = 0;
// what to use to sort items in the result set, currently not enabled
$sort = null;

// array for input constraints
$constraints = array();

if (@$_GET['habitat'] && @$_GET['habitat'] != '') {
    $constraints['habitat'] = explode(";",$_GET['habitat']);
}

if (@$_GET['dissolved_oxygen'] && @$_GET['dissolved_oxygen'] != '') {
    $constraints['dissolved_oxygen'] = explode(";",$_GET['dissolved_oxygen']);
}

if (@$_GET['absolute_depth_beta'] && @$_GET['absolute_depth_beta'] != '') {
    $constraints['absolute_depth_beta'] = explode(";",$_GET['absolute_depth_beta']);
}

if (@$_GET['specific_conductance'] && @$_GET['specific_conductance'] != '') {
    $constraints['specific_conductance'] = explode(";",$_GET['specific_conductance']);
}

if (@$_GET['depth_start'] && @$_GET['depth_start'] != '') {
    $constraints['depth_start'] = explode(";",$_GET['depth_start']);
}

if (@$_GET['depth_end'] && @$_GET['depth_end'] != '') {
    $constraints['depth_end'] = explode(";",$_GET['depth_end']);
}

if (@$_GET['fecal_coliform'] && @$_GET['fecal_coliform'] != '') {
    $constraints['fecal_coliform'] = explode(";",$_GET['fecal_coliform']);
}

if (@$_GET['collection_time'] && @$_GET['collection_time'] != '') {
    $constraints['collection_time'] = explode(";",$_GET['collection_time']);
}

if (@$_GET['temperature'] && @$_GET['temperature'] != '') {
    $constraints['temperature'] = explode(";",$_GET['temperature']);
}

if (@$_GET['conductivity'] && @$_GET['conductivity'] != '') {
    $constraints['conductivity'] = explode(";",$_GET['conductivity']);
}

if (@$_GET['sample_type'] && @$_GET['sample_type'] != '') {
    $constraints['sample_type'] = explode(";",$_GET['sample_type']);
}

if (@$_GET['dissolved_oxygen_2'] && @$_GET['dissolved_oxygen_2'] != '') {
    $constraints['dissolved_oxygen_2'] = explode(";",$_GET['dissolved_oxygen_2']);
}

if (@$_GET['volume_filtered'] && @$_GET['volume_filtered'] != '') {
    $constraints['volume_filtered'] = explode(";",$_GET['volume_filtered']);
}

if (@$_GET['environmental_zone'] && @$_GET['environmental_zone'] != '') {
    $constraints['environmental_zone'] = explode(";",$_GET['environmental_zone']);
}

if (@$_GET['precipitation'] && @$_GET['precipitation'] != '') {
    $constraints['precipitation'] = explode(";",$_GET['precipitation']);
}

if (@$_GET['longhurst_long_name'] && @$_GET['longhurst_long_name'] != '') {
    $constraints['longhurst_long_name'] = explode(";",$_GET['longhurst_long_name']);
}

if (@$_GET['redox_state'] && @$_GET['redox_state'] != '') {
    $constraints['redox_state'] = explode(";",$_GET['redox_state']);
}

if (@$_GET['latitude_longitude'] && @$_GET['latitude_longitude'] != '') {
    $constraints['latitude_longitude'] = explode(";",$_GET['latitude_longitude']);
}

if (@$_GET['salinity'] && @$_GET['salinity'] != '') {
    $constraints['salinity'] = explode(";",$_GET['salinity']);
}

if (@$_GET['longhurst_zone'] && @$_GET['longhurst_zone'] != '') {
    $constraints['longhurst_zone'] = explode(";",$_GET['longhurst_zone']);
}

if (@$_GET['in_project'] && @$_GET['in_project'] != '') {
    $constraints['in_project'] = explode(";",$_GET['in_project']);
}

if (@$_GET['IHO_area'] && @$_GET['IHO_area'] != '') {
    $constraints['IHO_area'] = explode(";",$_GET['IHO_area']);
}

if (@$_GET['limit'] && @$_GET['limit'] != '') {
    $limit = $_GET['limit'];
}

if (@$_GET['offset'] && @$_GET['offset'] != '') {
    $offset = $_GET['offset'];
}

if (@$_GET['request'] && @$_GET['request'] != '') {
    $type = $_GET['request'];
}


// instantiate the Config class for the dataset browser (class
// definition in "datasets.php")
$s2s = new VAMPS_Dataset_S2SConfig();

// get the response for the request given the type of request, the
// constraints list to constrain the result, the number of results to
// pull back, the offset into the result set, and what to sort the
// results by. For a facet the response will be a json object. For
// the result set the response will be an HTML document
$out = $s2s->getResponse(@$type, @$constraints, @$limit, @$offset, @$sort);

// for sending the response we want to know the number of characters in
// the result.
$size = strlen($out);

// set the size of the response in the response header
header("Content-length: $size");

// echo the response
echo $out;
