<?php

include_once("../utils.php");
// parent class S2SConfig
include_once("../config.php");

class VAMPS_Dataset_S2SConfig extends S2SConfig {
	
	private $namespaces = array(
		'vamps'	=> "http://vamps.mbl.edu/schema#",
		'bibo'	=> "http://purl.org/ontology/bibo/",
		'foaf'	=> "http://xmlns.com/foaf/0.1/",
		'rdfs'	=> "http://www.w3.org/2000/01/rdf-schema#",
		'time'	=> "http://www.w3.org/2006/time#",
		'xsd'	=> "http://www.w3.org/2001/XMLSchema#",
		'skos'	=> "http://www.w3.org/2004/02/skos/core#",
		'owl'	=> "http://www.w3.org/2002/07/owl#",
		'dct'	=> "http://purl.org/dc/terms/",
		'dc'	=> "http://purl.org/dc/elements/1.1/",
		'obo'	=> "http://purl.obolibrary.org/obo/"
	);

	/**
	* Return SPARQL endpoint URL
	* @return string SPARQL endpoint URL
	*/
	public function getEndpoint() {
		return "https://vamps.tw.rpi.edu/virtuoso/sparql";
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
	
		$options = array(
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 120
		);
				
		$encoded_query = 'query=' . urlencode($query) . urlencode('&format=application/sparql-results+xml');
		return execSelect($this->getEndpoint(), $encoded_query, $options);
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
	* Create HTML of search result
	* @param array $result query result to be processed into HTML
	* @return string HTML div of search result entry
	*/
	public function getSearchResultOutput(array $result) {

		$html = "<div class='result-list-item'>";
		$html .= "</div>";
		return $html;
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
				$header .= "?dataset ?label";
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
			case "communities":
				$body .= "?dataset a vivo:Dataset . ";
				$body .= "?dataset  dco:associatedDCOCommunity ?id . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;
				
			case "groups":
				$body .= "?dataset a vivo:Dataset . ";
				$body .= "?dataset dco:associatedDCOPortalGroup ?id . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;

			case "authors":
				$body .= "?dataset a vivo:Dataset . ";
				$body .= "?dataset vivo:relatedBy [vivo:relates ?id] . ";
				$body .= "?id a foaf:Person . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;

			case "other_authors":
				$body .= "?dataset a vivo:Dataset . ";
				$body .= "?dataset dco:authorName ?id . ";
				$body .= "BIND(str(?id) AS ?label) . ";
				break;

			case "projects":
				$body .= "?dataset a vivo:Dataset . ";
				$body .= "?project a vivo:Project . ";
				$body .= "?project rdfs:label ?l . ";
				$body .= "?project dco:relatedDataset ?dataset . ";
				$body .= "BIND(str(?project) AS ?id) . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;

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
				$body .= "?dataset rdfs:label ?l . ";
				// $body .= "OPTIONAL { ?dataset dco:hasDcoId ?id . } ";
				// $body .= "OPTIONAL { ?dataset dco:yearOfPublication ?y . } ";
				// $body .= "OPTIONAL { ?dataset dco:associatedDCOCommunity ?comm . ?comm rdfs:label ?c_l . } ";
				// $body .= "OPTIONAL { ?dataset dco:associatedDCOPortalGroup ?gp . ?gp rdfs:label ?g_l . } ";
				// $body .= "OPTIONAL { ?project dco:relatedDataset ?dataset ; rdfs:label ?pl . } ";
				// $body .= "OPTIONAL { ?dataset obo:ERO_0000045 ?acc . } ";
				$body .= "BIND(str(?l) AS ?label) . ";
				// $body .= "BIND(str(?id) AS ?dco_id) . ";
				// $body .= "BIND(str(?y) AS ?year) . ";
				// $body .= "BIND(str(?c_l) AS ?comm_label) . ";
				// $body .= "BIND(str(?g_l) AS ?gp_label) . ";
				// $body .= "BIND(str(?acc) AS ?access) . ";
				// $body .= "BIND(str(?pl) AS ?project_label) . ";
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
	
    /**
     * For each selection in a facet add a link to the context for that selection
     *
     * using the individual link for the different types as the context
     * for the selection
     *
     * @param array $results selections to add context to
	 * @param string $type search type (e.g. 'datasets', 'authors', 'keywords')
     */
	private function addContextLinks(&$results, $type) {
		
		if ($type == "communities" || $type == "groups" || $type == "authors" || $type == "projects") {
			foreach ( $results as $i => $result ) {
				$results[$i]['context'] = $result['id']; 
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
			return $this->getSearchResultsOutput($results, $limit, $offset, $count);
		}
		// Output for other types of requests (i.e. search facets)
		else {		
			$this->addContextLinks($results, $type);
			return $this->getFacetOutput($results);
		}
	}
}
