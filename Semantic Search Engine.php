<?php

require('semsol/ARC2.php'); // allows to query a SPARQL endpoint.
require(__DIR__.'\vendor\solarium\solarium\examples/init.php'); // allows to query the Solr index.
htmlHeader();

// create a client instance
$client = new Solarium\Client($config);
//Use POST method instead of GET to managee long queries
$client->getPlugin('postbigrequest');

$search = array();  



//when the search button is pressed
if( isset($_POST['btn'])){


   // store the current number of search boxes 
   $sUnitNumber = $_POST['count'];


      // loop to repeat the operation for each search box
      while ( $sUnitNumber >= 0) {  
          $title = "";
          $content = "";
          $url = "";
          $OR1 = "";
          $OR2 = "";
          $OR3 = " OR ";
          $AND =" AND ";

          $keywords =htmlentities( ucfirst($_POST['keywords'.$sUnitNumber]));

          $bagOfKeywords = array();

          //create the bag of keywords
          $bagOfKeywords = create_bag($keywords);



              // build the query for the index
              $search[] = '(';
              $arrlength = count($bagOfKeywords);

              //loop to repeat the operation on each term from the bag of keyword
              for($x = 0; $x < $arrlength; $x++) {
                   
                   $keywords = $bagOfKeywords[$x];

                   //check the filters selected by the user, and consequently adapt the query syntax
                  
                  if (isset($_POST['url'.$sUnitNumber])) {
                      $url = 'url:"'.$keywords.' "';
                      $OR1 =" OR ";
                      $OR2 = " OR ";
                
                  }

                  if (isset($_POST['content'.$sUnitNumber])) {
                      $content = 'content:"'.$keywords.' "'.$OR2;
                      $OR1 = " OR ";
                  }

                  if (isset($_POST['title'.$sUnitNumber])) {
                      $title = 'title:"'.$keywords.'"'.$OR1;
                  }
                   
                   

                  $searchTerm = $title.$content.$url;
                  $search[] = $searchTerm;
                  $search[] = $OR3;
              }

          //When the loop is ended, the last 'OR' has to be removed
          array_pop($search);
          $search[] = ')';
          // Add an 'AND' for the bag of keywords from the nex search box
          $search[] = $AND;
          $sUnitNumber--;
      }

      //When all the search boxes have been considered, remove the last "AND"
      array_pop($search);

?>

<script type="text/javascript"> 
//reset the count number of keyword searchboxes 
 
$(document).ready(function resetCount(){
    resetCount = 0;
     document.getElementById('count').value = resetCount;});
</script>


<?php



 // get a select query instance
$query = $client->createSelect();

$indexQuery = implode($search);

// apply settings 
$query->setQuery($indexQuery);


//set the search fields
$query->setFields(array('url','title','content'));


// this executes the query and returns the result
$resultset = $client->select($query);

// get the number of results
$numFound = $resultset->getNumFound();

}


//function to build a bag of keyword. It manipulates the original keyword in order to query the dbpedia SPARQL endpoint.
function create_bag($keywords){

                 $bagOfKeywords = array();
                 $bagOfKeywords[] = $keywords;
                     

                // set dbpedia SPARQL endpoint as a target
                $dbendpoint = array(
                "remote_store_endpoint" => "http://dbpedia.org/sparql",
                 );
               
                //error management
                $store = ARC2::getRemoteStore($dbendpoint);
                

                //in dbpedia page when the title is composed by two or more terms, the word are separated by an uderscore
                //Therefore we replace whitespaces with underscores
                $keywords= preg_replace("/[\s+]/", "_", $keywords);

                //When a term composed by to word redirects to a main page, the two terms are separated by whitespaces, not underscore. Therefore we replace underscore with whitespaces and we query also for this version of the original keyword.                     
                $keywordsSpaced= preg_replace("/[^a-zA-Z]/", ' ', $keywords);

                //this query retrieve the URL of the dbpedia page of the modified keyword
                $query = '
                        PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
                        PREFIX dbo: <http://dbpedia.org/ontology/>
                          
                          SELECT ?redirectsTo WHERE {

                            { ?x rdfs:label "'.$keywords.'"@en .}

                          UNION

                            {?x rdfs:label "'.$keywordsSpaced.'"@en .}

                            ?x dbo:wikiPageRedirects ?redirectsTo
                          } 
                        ';
                   
                   
                $rows = $store->query($query, 'rows'); /* execute the query */

                     
                   

                foreach( $rows as $row ) { /* loop for each returned row */
                      
                      //store the URL
                       $res = $row['redirectsTo']; 

                       //extract the name of the page from its URL
                       $resExploded = explode('http://dbpedia.org/resource/', $res) ; 

                       //every dbpage can redirect to another one page only, so there will be always only one element 
                       $mainRef = $resExploded[1]; 
                }



                
                // if the dbpedia page related to the original keyword redirects to another page,
                //the name of the main page becomes the new keyword
                if (!empty($mainRef)) {
                  $keywords = $mainRef;

                  //add the new keyword to the bag of keywords
                  $bagOfKeywords[] = $keywords;
                }


                // this query retrieve all dbpedia page URLs that redirect to the page named like the current keyword
                $query2 = '
                          select ?x
                          where {
                          ?x <http://dbpedia.org/ontology/wikiPageRedirects> <http://dbpedia.org/resource/'.$keywords.'>
                          }                    
                    ';
               
               
                $rows2 = $store->query($query2, 'rows'); /* execute the query */


                //Each term is extracted from the URL and added to the bag of keywords
                foreach( $rows2 as $row2 ) { /* loop for each returned row */
                       
                       $res2 = $row2['x'];
                       $resExploded2 = explode('http://dbpedia.org/resource/', $res2) ;
                       $result2 = $resExploded2[1];
                       $bagOfKeywords[] = preg_replace("/[^a-zA-Z]/", ' ', $result2);
                }

                return $bagOfKeywords;
}
     
?>    


<!DOCTYPE HTML>  
<html>

<head>
<meta charset="utf-8">
 <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
 <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
 <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
 <title>SEARCH ENGINE</title>
</head>


<body>  
    <div class="text-center mb-3" id="titlediv">
        <h1 id="title"> Semantic Search Engine</h1>
    </div>
    <div class="row" id="row">
    <div class="col-md-1"></div>
    <div class="col-md-10" id="panelcent">    
        <form action="Semantic Search Engine.php" method="POST" id="form">  

          <div id="searchCont">

            <!-- searching unit-->
          <div class="mb-3 pb-1 border-bottom  border-dark" id="searchPanel">
            <div class="row" id="searchbox">
              <div class="col-md-6">
                <label for="keywords">Keyword</label>
                  <div class="input-group">

                    <!-- the default searchbox is the number zero-->
                    <input type="text" class="form-control" id="keywords" name="keywords0">
                  </div>
              </div>
              <div class="col-md-6 d-flex align-items-end">
                <div id="searchLocation">
                  <span>
                  <label>Search the keyword in:</label>
                  </span>
                  <span>
                  <label class="radio-inline">
                      <input type="checkbox" name="title0"  checked="checked"> title <br>
                  </label>
                  <label class="radio-inline">
                       <input type="checkbox" name="content0"  checked="checked"> content<br>
                  </label>
                  <label class="radio-inline">
                       <input type="checkbox" name="url0"  checked="checked"> url<br>
                  </label>
                  </span>
                  </div>    
              </div>
            </div>
            </div>
          </div>

           <div>          
              <button type="button" name="add" id="add" value = "add" class="btn btn-primary">Add a search box
              </button>
           </div>

             <!-- keep the count of currently present searchboxes -->
            <input type="hidden" name="count" id="count" value="0" />

            <br><br>

          <button type="submit" class="btn btn-success" id="btn" name="btn" value="btn">   
           Search 
          </button>
          <br>

      </form>
      <div>
      
        <button class="btn btn-dark" data-toggle="collapse" data-target="#showquery" onclick="change()" id="show_hide_query"> Show query</button>

          <div id="showquery" class="collapse">
            <?php 
            if (isset($_POST['btn'])) {
              
            echo($indexQuery."<br>"); 
           }
           else{
            echo "There is not a query yet";
           }
            ?>
          </div>
        </div>
        <br>

      <div id="divresult">
         <table class="table table-hover">
          <?php
          if (isset($_POST['btn'])) {
              

            // display the total number of documents found by solr
            
            echo $numFound.' results';

            // show documents using the resultset iterator
            foreach ($resultset as $document) {


                
                echo '<tr><th>&#8226</th><td>' . $document->title . '<br><a href='.$document->url.' target="_blank">'.$document->url.'</a> </td></tr>';
                
            }          }
          ?>
        </table>
        <br><br>
      </div>
  </div>
  </div>

</body>
</html>


<script>

    //add a new search box for another keyword and assign a specific value to it on the basis of the current number of searchboxes. The value of the first new searchbox will be 1, the value of the second will be 2 and so on.
  $(document).ready(function(){  
      var i=0;  
      $('#add').click(function(){  
           i++;  
           $('#searchCont').append('<div class="mb-3 pb-1 border-bottom  border-dark" id="searchPanel"><div class="row" id="searchbox"><div class="col-md-6"><label for="keywords">Keyword</label><div class="input-group"><input type="text" class="form-control" id="keywords" name="keywords'+i+'"></div></div><div class="col-md-6 d-flex align-items-end"><span><label>Search the keyword in:</label></span><span><label class="radio-inline"><input type="checkbox" name="title'+i+'" checked="checked"> title <br></label><label class="radio-inline"><input type="checkbox" name="content'+i+'" checked="checked"> content<br></label><label class="radio-inline"><input type="checkbox" name="url'+i+'" checked="checked"> url<br></label></span></div></div></div>'); 

           //increment the count of currently present searchboxes
           document.getElementById('count').value = i; 
      });    
  }); 

  //change the "show query" button on click
  function change() {
        var box = document.getElementById('show_hide_query');
        if (box.innerText == "Show query") {
          box.innerText = "Hide query";
        } else {
          box.innerText = "Show query";
        }
      }

 </script>

