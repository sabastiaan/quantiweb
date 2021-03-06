<?php
	function content_create_type($type, $aditional = null, $menu = 0, $way = 0){

		$_type = db_escape($type);
		if(db_entry_exist("content_types", array("type" => $_type)) == 0){
			if($aditional == null){
				db_insert("content_types", array(
					"type" => $_type,
					"menu" => $menu,
					"way" => $way
				));
			}
			else{
				db_insert("content_types", array(
					"type" => $_type,
					"menu" => $menu,
					"way" => $way,
					"aditional" => $aditional
				));
			}

		}
	}


	function content_get_type($name){
		$_name = db_escape($name);
		$query = db_select("*", "content_types", array(
			"type" => $_name
		));

		if(db_numrows($query) == 0){
			return "There is no content type with this name";
		}

		while($row = db_grab($query)){
			return $row['type_id'];
		}
	}

	function content_get_type_name($id){
		$_id = db_escape($id);
		$query = db_select("*", "content_types", array(
			"type_id" => $_id
		));
		
		while($row = db_grab($query)){
			return $row['type'];
		}
	}

	function content_get_way($type){
		if(is_numeric($type)){
			$query = db_select("*", "content_type", array(
				"type_id" => $type
			));
		}
		else{
			$query = db_select("*", "content_type", array(
				"type" => $type
			));
		}
		while($row = db_grab($query)){
			return $row['way'];
		}
	}

	function content_search($query){
		$_query = "%" . $query . "%";
		$sqlquery = db_custom("SELECT * FROM content WHERE content LIKE '$_query'");
		//$sqlquery = db_select("*", "content", array(
		//	"content " => "$_query"
		//));
		return $sqlquery;
	}

	function content_query($type = 00, $way = "static", $id = 0, $status = 0){
		$_type = db_escape($type);
		$_way = db_escape($way);
		$_id = db_escape($id);
		$_status = db_escape($status);

		if($_way == "static"){
			if($_id != 0){
				if($_type == 00){
					if($_status == 1){
						$query = db_select("*", "content", array(
							"content_id" => $_id
						));
					}
					else{
						$query = db_select("*", "content", array(
							"status" => 1,
							"content_id" => $_id
						));	
					}
				}
				else{
					if($_status == 1){
						$query = db_select("*", "content", array(
							"type" => $_type,
							"content_id" => $_id
						));
					}
					else{
						$query = db_select("*", "content", array(
							"type" => $_type,
							"status" => 1,
							"content_id" => $_id
						));
					}
				}



				return $query;
			}
			else{
				die("Error: no post");
			}
		}
		elseif($_way == "loop"){
			if($_type == 00){
				if($_status == 1){
					$query = db_select("*", "content", array(
						"1" => "1"
					),0, array(
						"content_id" => "DESC"
					));
				}
				else{
					$query = db_select("*", "content", array(
						"status" => 1
					),0, array(
						"content_id" => "DESC"
					));
				}
			}
			else{
				if($_status == 1){
					$query = db_select("*", "content", array(
						"type" => $_type,
					),0, array(
						"content_id" => "DESC"
					));
				}
				else{
					$query = db_select("*", "content", array(
						"type" => $_type,
						"status" => 1
					),0, array(
						"content_id" => "DESC"
					));
				}
			}


			return $query;
		}
		else{
			return("Error");
		}
	}

	function content_get_title($id){
		$query = db_select("*", "content", array(
			"content_id" => $id
		));

		while($row = db_grab($query)){
			return $row['title'];
		}
	}

	function content_get_page_title(){
		if(isset($_GET['p'])){
			$page = $_GET['p'];
			if(is_numeric($page)){
				$get_pages = db_select("title, content_id", "content", array(
					"content_id" => $page
				));

				while($row2 = mysqli_fetch_array($get_pages)){
					return $row2['title'];
				}
			}
			else{
				return ucfirst($_GET['p']);
			}
		}
		else{
			return "Home";
		}

	}

	function content_get_content(){
		if(isset($_GET['p'])){
			$id = $_GET['p'];
			if(is_numeric($id)){
				$query = content_query(00, "static", $id);

				while($row = db_grab($query)){
					echo $row['content'];
					$additionalfunction = content_getadditionalfunction($row['type']);
                    $query = db_select("*", "content", array(
                        "content_id" => $id
                    ));
                    while($row2 = db_grab($query)){
                        $views = $row2['views'];
                        $views++;
                    }
                    db_update("content", array(
                        "views" => $views
                    ),
                    array(
                        "content_id" => $id
                    ));
                    if(function_exists($additionalfunction)){
                        $additionalfunction();
                    }
                    else{
                        
                    }
				}
			}
			else{
				$query = content_query(content_get_type($id), "loop", $id);

				while($row = db_grab($query)){
					echo "<h2>" . $row['title'] . "</h2>";
					echo substr($row['content'], 0, 500) . "...";
					echo "</p><br>" . content_readmore($row['content_id']);
                    
				}
			}
		}
		elseif(isset($_GET['s'])){
			$getresult = str_replace("%20", " " ,$_GET['s']);
			$result = content_search($getresult);	
			while($row = db_grab($result)){
				echo "<h2>" . $row['title'] . "</h2>";
				echo substr($row['content'], 0, 500) . "...";
				echo "<br>" . content_readmore($row['content_id']);

			}
		}
		else{
			$function = system_getsetting("index");
			if(function_exists($function)){
				$function();
			}
			else{
				echo "You don't have any plugins activated. <a href=\"admin.php\">Activate a plugin</a>";
			}
		}

	}

	function content_get_sidebar(){
		//TODO: editable!
		echo "<h2>Search</h2>";
		?>
		<form method="GET" action="index.php">
			<div class="input-group">
				<input type="text" name="s" class="form-control" placeholder="Search for...">
    			<span class="input-group-btn">
        			<button class="btn btn-default" type="submit">Go!</button>
      			</span>
    		</div><!-- /input-group -->
			
		</form>
		<?php
	}

    function content_getadditionalfunction($contenttype){
        $query = db_select("*", "content_types", array(
            "type_id" => $contenttype
        ));
        
        while($row = db_grab($query)){
            return $row['aditional'];
        }
        
    }

	function content_add($title, $content, $author, $status, $type){


		db_insert("content", array(
			"title" => $title,
			"content" => $content,
			"author" => $author,
			"status" => $status,
			"type" => $type
		));
	}

	function content_modify($id, $content){
		db_update("content", array(
			"content" => $content
		), array(
			"content_id" => $id
		));

	}

	function content_readmore($id){
		return '<a href="?p=' . $id . '">Read more...</a>';
	}

	function content_modify_info($id, $info){
		db_update("content", $info, array(
			"content_id" => $id
		));
	}

	function content_delete($id){
		db_delete("content", array(
			"content_id" => $id
		),1);
	}

	function content_nav(){
		$query = db_select("*", "content_types", array(
			"menu" => 1
		));
		$nav = "";
		$nav = $nav . '<li><a href="index.php">Home</a></li>';

		while($row = db_grab($query)){
			if($row['way'] == 0){
				//static page per content
				$get_pages = db_select("title, content_id", "content", array(
					"type" => $row['type_id']
				));
				while($row2 = mysqli_fetch_array($get_pages)){
					$nav = $nav . "<li><a>" . $row2['title'] . "</a></li>";
				}
			}
			else{
				//loop on 1 page (blog)
				$nav = $nav . '<li><a href="index.php?p=' . $row['type'] . '">' . ucfirst($row['type']) . '</a></li>';

			}
		}
		return $nav;
	}
?>
