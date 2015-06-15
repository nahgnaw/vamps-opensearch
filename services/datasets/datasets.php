<?php

include_once("../utils.php");
// parent class S2SConfig
include_once("../config.php");

class VAMPS_Dataset_S2SConfig extends S2SConfig {
	
	private $namespaces = array(
		'vamps'	=> "http://vamps.mbl.edu/schema#",
		'dco'	=> "http://deepcarbon.net/schema#",
		'vivo'	=> "http://vivoweb.org/",
		'rdfs'	=> "http://www.w3.org/2000/01/rdf-schema#",
		'xsd'	=> "http://www.w3.org/2001/XMLSchema#",
		'skos'	=> "http://www.w3.org/2004/02/skos/core#",
		'owl'	=> "http://www.w3.org/2002/07/owl#",
		'dct'	=> "http://purl.org/dc/terms/",
		'dc'	=> "http://purl.org/dc/elements/1.1/"
	);

	private $datatype_property_labels = array(
		"habitat", "specific conductance", "absolute depth beta", "dissolved oxygen", "collection time", "conductivity", "dissolved oxygen 2", "environmental zone",
		"fecal coliform", "funding", "id", "latitude langitude", "longhurst long name", "notes", "precipitation", "public", "redox state", "revised project name",
		"salinity", "sample ID", "sample type", "temperature", "volume filtered"
	);

	private $object_property_labels = array(
		"has dataset", "IHO area", "in project", "longhurst zone", "owner"
	);
	
	public function concatenate_label($label) {
		return implode('_', explode(' ', $label));
	}

	/**
	* Return SPARQL endpoint URL
	* @return string SPARQL endpoint URL
	*/
	public function getEndpoint() {
		return "http://vamps.tw.rpi.edu/virtuoso/sparql";
	}

	/**
	* Return array of prefix, namespace key-value pairs
	* @return array of prefix, namespace key-value pairs
	*/
	public function getNamespaces() {
		return $this->namespaces;
	}
	
	/**
	* Execute SPARQL select query
	* @param string $query SPARQL query to execute
	* @return array an array of associative arrays containing the bindings of the query results
	*/
	public function sparqlSelect($query) {
		//echo htmlentities($query);	
		$options = array(
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 120
		);
				
		$encoded_query = 'query=' . urlencode($query) . '&' . urlencode('format=application/sparql-results+xml');
		return execSelect($this->getEndpoint(), $encoded_query, $options);
	}

	public function getDatasetDatatypePropertyValue($dataset, $datatypeProperty) {
		$query = $this->getPrefixes();
		$query .= "SELECT DISTINCT ?value WHERE { ";
		$query .= "?p rdfs:label \"$datatypeProperty\"@en . ";
		$query .= "OPTIONAL { <$dataset> ?p ?v . BIND(str(?v) AS ?value) }} ";
		return $this->sparqlSelect($query);		
	}

	public function getDatasetObjectPropertyValue($dataset, $objectProperty) {
		$query = $this->getPrefixes();
		$query .= "SELECT DISTINCT ?value WHERE { ";
		$query .= "?p rdfs:label \"$objectProperty\"@en . ";
		$query .= "OPTIONAL { <$dataset> ?p [rdfs:label ?v] . BIND(str(?v) AS ?value) }} ";
		return $this->sparqlSelect($query);		
	}

	/**
	* Return count of total search results for specified constraints
	* @param array $constraints array of arrays with search constraints
	* @result int search result count
	*/
	public function getSearchResultCount(array $constraints) {
		
		$query = $this->getSelectQuery("count", $constraints);
		$results = $this->sparqlSelect($query);
		$result = $results[0];
		return $result['count'];
	}
	
	/**
	* Return SPARQL query header component
	* @param string $type search type (e.g. 'datasets', 'authors', 'keywords')
	* @return string query header component (e.g. 'SELECT ?id ?label')
	*/
	public function getQueryHeader($type) {
	
		$header = "";
		switch($type) {
			case "datasets":
				$header .= "?dataset ?label "; 
				// $header .= '(GROUP_CONCAT(DISTINCT ?comm ; SEPARATOR=",") AS ?community) ';
				break;
			case "count":
				$header .= "(count(DISTINCT ?dataset) AS ?count)";
				break;
			default:
				$header .= "?id ?label (COUNT(DISTINCT ?dataset) AS ?count)";
				break;
		}
		return $header;
	}
	
	/**
	* Return SPARQL query footer component
	* @param string $type search type (e.g. 'datasets', 'authors', 'keywords')
	* @param int $limit size of result set
	* @param int $offset offset into result set
	* @param string $sort query result sort parameter
	* @return string query footer component (e.g. 'GROUP BY ?label ?id')
	*/
	public function getQueryFooter($type, $limit=null, $offset=0, $sort=null) {
	
		$footer = "";
		switch($type) {
			case "datasets":
				$footer .= " GROUP BY ?dataset";
				$footer .= " ORDER BY ?label";
				if ($limit)	$footer .= " LIMIT $limit OFFSET $offset";
				break;
			case "count":
				break;
			default:
				$footer .= " GROUP BY ?label ?id";
				break;
		}
		return $footer;
	}
	
	/**
	  * Return SPARQL query WHERE clause minus constraint clauses for specified search type
	  * @param string $type search type (e.g. 'datasets', 'authors', 'keywords')
	  * @return string WHERE clause component minus constraint clauses (e.g. '?dataset a dcat:Dataset . ')
	  */
	public function getQueryBody($type) {
		
		$body = "";
		switch($type) {
			case "years":
				$body .= "?dataset a vivo:Dataset . ";
				$body .= "?dataset dco:yearOfPublication ?id . ";
				$body .= "BIND(str(?id) AS ?label) . ";
				break;
				
			case "count":
				$body .= "?dataset a vivo:Dataset . ";
				break;
				
			case "datasets":
				$body .= '?dataset a [rdfs:label "Dataset"@en] . ';
				$body .= '?dataset rdfs:label ?l . ';
				$body .= 'BIND(str(?l) AS ?label) . ';
				break;
		}
				
		return $body;
	}
	
	/**
	* Return constraint clause to be included in SPARQL query
	* @param string $constraint_type constraint type (e.g. 'keywords')
	* @param string $constraint_value constraint value (e.g. 'Toxic')
	* @return string constraint clause to be included in SPARQL query
	*/	
	public function getQueryConstraint($constraint_type, $constraint_value) {
		
		$body = "";
		switch($constraint_type) {
			case "communities":
				$body .= "{ ?dataset dco:associatedDCOCommunity <$constraint_value> }";
				break;
			case "groups":
				$body .= "{ ?dataset dco:associatedDCOPortalGroup <$constraint_value> }";
				break;
			case "authors":
				$body .= "{ ?dataset vivo:relatedBy [vivo:relates <$constraint_value>] }";
				break;
			case "other_authors":
				$body .= "{ ?dataset dco:authorName \"" . urldecode($constraint_value) . "\" }";
				break;
			case "projects":
				$body .= "{ <$constraint_value> dco:relatedDataset ?dataset }";
				break;
			case "years":
				$body .= "{ ?dataset dco:yearOfPublication \"$constraint_value\"^^xsd:gYear }";
				break;
			default:
				break;
		}
		return $body;
	}
	
	public function getSearchResultOutput(array $result) {
		$html = "<div class='result-list-item'></div>";
		return $html;
	}

	public function addFieldsToResults(&$results, $fields, $fieldType) {
		foreach($results as $ind => $result) {
                	foreach($fields as $field) {
				if($fieldType == "datatype")
                        		$values = $this->getDatasetDatatypePropertyValue($result['dataset'], $field);
				else if($fieldType == "object")
                        		$values = $this->getDatasetObjectPropertyValue($result['dataset'], $field);
                                if(count($values) > 0) {
					foreach($values as $value) {
						if(isset($value['value']))  $results[$ind][$this->concatenate_label($field)] = $value['value'];
					}
				}
                       	}
                }
	}
	
	/**
	* Return representation (HTML or JSON) of response to send to client
	* @param array $results array of associative arrays with bindings from query execution
	* @param string $type search type (e.g. 'datasets', 'authors', 'keywords')
	* @param array $constraints array of arrays with search constraints
	* @param int $limit size of result set
	* @param int $offset offset into result set
	* @return string representation of response to client
	*/
	public function getOutput(array $results, $type, array $constraints, $limit=0, $offset=0) {
		
		// Output for request type "datasets"	
		if($type == "datasets") {
			$count = $this->getSearchResultCount($constraints);
			$this->addFieldsToResults($results, $this->datatype_property_labels, "datatype");	
			$this->addFieldsToResults($results, $this->object_property_labels, "object");	
			return $this->getFacetOutput($results);
		}
		// Output for other types of requests (i.e. search facets)
		else {		
			$this->addContextLinks($results, $type);
			return $this->getFacetOutput($results);
		}
	}
}
