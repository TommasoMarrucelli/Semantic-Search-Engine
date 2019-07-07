<?php

//USED AS BENCHMARK TO EVALUATE THE PERFORMANCES OF THE "SEMANTIC SEARCH ENGINE".


require(__DIR__.'\vendor\solarium\solarium\examples/init.php');
htmlHeader();

// create a client instance
$client = new Solarium\Client($config);

   
   
    $search = array();  
     




    if( isset($_POST['btn'])){

         $i = $_POST['count'];


            while ( $i >= 0) {  
                $title = "";
                $content = "";
                $url = "";
                $OR1 = "";
                $OR2 = "";
                $AND =" AND ";



              $keywords =htmlentities(trim( $_POST['keywords'.$i]));

                

                if (isset($_POST['url'.$i])) {
                    $url = '(url:"'.$keywords.' ")';
                    $OR1 =" OR ";
                    $OR2 = " OR ";
              
                }

                if (isset($_POST['content'.$i])) {
                    $content = '(content:"'.$keywords.' ")'.$OR2;
                    $OR1 = " OR ";
                }

                if (isset($_POST['title'.$i])) {
                    $title = '(title:"'.$keywords.'")'.$OR1;
                }
                 
                 

                $searchTerm = '('.$title.$content.$url.')';
                $search[] = $searchTerm;
                $search[] = $AND;
                $i--;
            }

            //remove the last "AND"
            array_pop($search);

?>

<script type="text/javascript"> 
//reset the count number of keywords
 
$(document).ready(function resetCount(){
    resetCount = 0;
     document.getElementById('count').value = resetCount;});
</script>


<?php



        // get a select query instance
$query = $client->createSelect();
// apply settings using the API
$keySearch = implode($search);
$query->setQuery($keySearch);
$query->setFields(array('url','title','content'));


// this executes the query and returns the result
$resultset = $client->select($query);
$numFound = $resultset->getNumFound();

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
 <title>SIMPLE SEARCH ENGINE</title>
</head>


<body>  
    <div class="text-center mb-3" id="titlediv">
        <h1 id="title" class="bg-danger"> Simple Search Engine</h1>
    </div>
     <div class="row" id="row">
    <div class="col-md-1"></div>
    <div class="col-md-10" id="panelcent"> 

        <form action="Traditional Search Engine.php" method="POST" id="form">
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
              <div class="col-md-6  d-flex align-items-end">               
                  <span >
                  <label>Search the keyword in:</label>
                  </span>
                  <span>
                  <label class="radio-inline">
                      <input type="checkbox" name="title0" value="title0" checked="checked"> title <br>
                  </label>
                  <label class="radio-inline">
                       <input type="checkbox" name="content0" value="content0" checked="checked"> content<br>
                  </label>
                  <label class="radio-inline">
                       <input type="checkbox" name="url0" value="url0" checked="checked"> url<br>
                  </label>
                  </span>             
              </div>
            </div>
            </div>
          </div>
          
           <div>          
            <button type="button" name="add" id="add" value = "add" class="btn btn-primary">Add a search box</button>
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
          <?php
          if (isset($_POST['btn'])) {
              

            // display the total number of documents found by solr
            echo($keySearch."<br>");
            echo $numFound.' results';

            // show documents using the resultset iterator
            foreach ($resultset as $document) {


                echo '<hr/><table>';
                echo '<tr><th>&#8226</th><td>' . $document->title . '</td></tr>';
                echo '<tr><th></th><td> <a href='.$document->url.' target="_blank">'.$document->url.'</a> </td></tr>';
                echo '</table>';
            }          }
          ?>
      </div>
  </div>
  </div>
       
</body>
</html>


<script>
  document.title = "SEARCH ENGINE"
    //add a new search box for another keyword
 $(document).ready(function(){  
      var i=0;  
      $('#add').click(function(){  
           i++;  
           $('#searchCont').append('<div class="mb-3 pb-1 border-bottom  border-dark" id="searchPanel"><div class="row" id="searchbox"><div class="col-md-6 "><label for="keywords">Keyword</label><div class="input-group"><input type="text" class="form-control" id="keywords" name="keywords'+i+'"></div></div><div class="col-md-6 d-flex align-items-end"><span><label>Search the keyword in:</label></span><span><label class="radio-inline"><input type="checkbox" name="title'+i+'" value="title'+i+'" checked="checked"> title <br></label><label class="radio-inline"><input type="checkbox" name="content'+i+'" value="content'+i+'" checked="checked"> content<br></label><label class="radio-inline"><input type="checkbox" name="url'+i+'" value="url'+i+'" checked="checked"> url<br></label></span></div></div></div>'); 

           document.getElementById('count').value = i; 
      });    
 });  
</script>

