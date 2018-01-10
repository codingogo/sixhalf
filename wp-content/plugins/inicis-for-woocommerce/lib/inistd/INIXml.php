<?php

//
// +----------------------------------------------------------------------+
// | <phpXML/> version 1.0                                                |
// | Copyright (c) 2001 Michael P. Mehl. All rights reserved.             |
// +----------------------------------------------------------------------+
// | Latest releases are available at http://phpxml.org/. For feedback or |
// | bug reports, please contact the author at mpm@phpxml.org. Thanks!    |
// +----------------------------------------------------------------------+
// | The contents of this file are subject to the Mozilla Public License  |
// | Version 1.1 (the "License"); you may not use this file except in     |
// | compliance with the License. You may obtain a copy of the License at |
// | http://www.mozilla.org/MPL/                                          |
// |                                                                      |
// | Software distributed under the License is distributed on an "AS IS"  |
// | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
// | the License for the specific language governing rights and           |
// | limitations under the License.                                       |
// |                                                                      |
// | The Original Code is <phpXML/>.                                      |
// |                                                                      |
// | The Initial Developer of the Original Code is Michael P. Mehl.       |
// | Portions created by Michael P. Mehl are Copyright (C) 2001 Michael   |
// | P. Mehl. All Rights Reserved.                                        |
// +----------------------------------------------------------------------+
// | Authors:                                                             |
// |   Michael P. Mehl <mpm@phpxml.org>                                   |
// +----------------------------------------------------------------------+
//

class XML
{
    var $nodes = array();
    var $ids = array();
    var $path = "";
    var $position = 0;
    var $root = "";
    var $xpath    = "";
    var $entities = array ( "&" => "&amp;", "<" => "&lt;", ">" => "&gt;",
        "'" => "&apos", '"' => "&quot;" );
    var $axes = array ( "child", "descendant", "parent", "ancestor",
        "following-sibling", "preceding-sibling", "following", "preceding",
        "attribute", "namespace", "self", "descendant-or-self",
        "ancestor-or-self" );
    var $functions = array ( "last", "position", "count", "id", "name",
        "string", "concat", "starts-with", "contains", "substring-before",
        "substring-after", "substring", "string-length", "translate",
        "boolean", "not", "true", "false", "lang", "number", "sum", "floor",
        "ceiling", "round", "text" );
    var $operators = array( " or ", " and ", "=", "!=", "<=", "<", ">=", ">",
        "+", "-", "*", " div ", " mod " );


		var $xml_node  = array();
		//modify by ddaemiri, 2007.05.28
		//load_file -> load_xml�� ���� �� string ���� ���� �Է¹��� �� ����.
    function XML ( $file = "" )
    {
        // Check whether a file was given.
        if ( !empty($file) )
        {
            // Load the XML file.
            return $this->load_xml($file, "");
        }
    }
		//modify by ddaemiri, 2007.05.28
		//load_file -> load_xml�� ���� �� string ���� ���� �Է¹��� �� ����.
    function load_xml ( $file, $str )
    {
        // Check whether the file exists and is readable.
        if ( (file_exists($file) && is_readable($file)) || $str != "")
        {
            // Read the content of the file.
						if( $str == "" )
            	$content = implode("", file($file));
						else 
            	$content = $str;

            // Check whether content has been read.
            if ( !empty($content) )
            {
                // Create an XML parser.
                $parser = xml_parser_create();
                
                // Set the options for parsing the XML data.
                xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1); 
                xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
                
                // Set the object for the parser.
                xml_set_object($parser, $this);
                
                // Set the element handlers for the parser.
                xml_set_element_handler($parser, "handle_start_element", "handle_end_element");
                xml_set_character_data_handler($parser, "handle_character_data");
                
                // Parse the XML file.
                if ( !xml_parse($parser, $content, true) )
                {
                    // Display an error message.
                    $this->display_error("XML error in file %s, line %d: %s",
                        $file, xml_get_current_line_number($parser),
                        xml_error_string(xml_get_error_code($parser)));
                }
                
                // Free the parser.
                xml_parser_free($parser);
								
								return OK;
            }
        }
        else
        {
            // Display an error message.
            //$this->display_error("File %s could not be found or read.", $file);
						return RESULT_MSG_FORMAT_ERR;
        }
    }
		
		//modify by ddaemiri, 2007.05.28
		//charset �߰�( header ���� )
    function make_xml ( $highlight = array(), $root = "", $level = 0, $charset = "UTF-8" )
		{
				// header �߰�
				$header = "<?xml version=\"1.0\" encoding=\"".$charset."\"?>"."\n";
				$body = $this->get_xml( $highlight, $root, $level );
				return $header.$body;
		}
		//modify by ddaemiri, 2007.05.28
		//get_file -> get_xml �� �Լ��̸� ����. 
    function get_xml ( $highlight = array(), $root = "", $level = 0 )
    {

        // Create a string to save the generated XML data.
        $xml = "";
        
        // Create two strings containing the tags for highlighting a node.
        $highlight_start = "<font color=\"#FF0000\"><b>";
        $highlight_end   = "</b></font>";
        
        // Generate a string to be displayed before the tags.
        $before = "";
        
        // Calculate the amount of whitespaces to display.
        for ( $i = 0; $i < ( $level * 2 ); $i++ )
        {
            // Add a whitespaces to the string.
            $before .= " ";
        }
        
        // Check whether a root node is given.
        if ( empty($root) )
        {
            // Set it to the document root.
            $root = $this->root;
        }
        
        // Check whether the node is selected.
        $selected = in_array($root, $highlight);
        
        // Now add the whitespaces to the XML data.
        $xml .= $before;
        
        // Check whether the node is selected.
        if ( $selected )
        {
            // Add the highlight code to the XML data.
            $xml .= $highlight_start;
        }
        
        // Now open the tag.
        $xml .= "<".$this->nodes[$root]["name"];
        
        // Check whether there are attributes for this node.
        if ( count($this->nodes[$root]["attributes"]) > 0 )
        {
            // Run through all attributes.
            foreach ( $this->nodes[$root]["attributes"] as $key => $value )
            {
                // Check whether this attribute is highlighted.
                if ( in_array($root."/attribute::".$key, $highlight) )
                {
                    // Add the highlight code to the XML data.
                    $xml .= $highlight_start;
                }
                
                // Add the attribute to the XML data.
                $xml .= " ".$key."=\"".trim(stripslashes($value))."\"";
                
                // Check whether this attribute is highlighted.
                if ( in_array($root."/attribute::".$key, $highlight) )
                {
                    // Add the highlight code to the XML data.
                    $xml .= $highlight_end;
                }
            }
        }
        
        // Check whether the node contains character data or has children.
        if ( $this->nodes[$root]["text"] == "" &&
            !isset($this->nodes[$root]["children"]) )
        {
            // Add the end to the tag.
            $xml .= "/";
        }
        
        // Close the tag.
        //$xml .= ">\n";
        $xml .= ">";
        
        // Check whether the node is selected.
        if ( $selected )
        {
            // Add the highlight code to the XML data.
            $xml .= $highlight_end;
        }
        
        // Check whether the node contains character data.
        if ( $this->nodes[$root]["text"] != "" )
        {
            // Add the character data to the XML data.
            //$xml .= $before."  ".$this->nodes[$root]["text"]."\n";
            //$xml .= $before.$this->nodes[$root]["text"];
            $xml .= $this->nodes[$root]["text"];
        }
        
        // Check whether the node has children.
        if ( isset($this->nodes[$root]["children"]) )
        {
            // Run through all children with different names.
            foreach ( $this->nodes[$root]["children"] as $child => $pos )
            {
                // Run through all children with the same name.
                for ( $i = 1; $i <= $pos; $i++ )
                {
                    // Generate the full path of the child.
                    $fullchild = $root."/".$child."[".$i."]";
                    
                    // Add the child's XML data to the existing data.
                    $xml .= "\n\t".$this->get_xml($highlight, $fullchild, $level + 1);
                }
            }
        }
        
        // Check whether there are attributes for this node.
        if ( $this->nodes[$root]["text"] != "" ||
            isset($this->nodes[$root]["children"]) )
        {
            // Add the whitespaces to the XML data.
            //$xml .= $before;
            
            // Check whether the node is selected.
            if ( $selected )
            {
                // Add the highlight code to the XML data.
                $xml .= $highlight_start;
            }
            
            // Add the closing tag.
            $xml .= "</".$this->nodes[$root]["name"].">";
            
            // Check whether the node is selected.
            if ( $selected )
            {
                // Add the highlight code to the XML data.
                $xml .= $highlight_end;
            }
            
            // Add a linebreak.
            //$xml .= "\n";
        }
        
        // Return the XML data.
        return $xml;
    }
    function add_node ( $context, $name, $value="", $attr_arr=NULL )
    {
        // Check whether a name for this element is already set.
        if ( empty($this->root) )
        {
            // Use this tag as the root element.
            $this->root = "/".$name."[1]";
        }
        
        // Calculate the full path for this element.
        $path = $context."/".$name;
        
        // Set the relative context and the position.
        $position = ++$this->ids[$path];
        $relative = $name."[".$position."]";
        
        // Calculate the full path.
        $fullpath = $context."/".$relative;
        
        // Calculate the context position, which is the position of this
        // element within elements of the same name in the parent node.
        $this->nodes[$fullpath]["context-position"] = $position;
        
        // Calculate the position for the following and preceding axis
        // detection.
        $this->nodes[$fullpath]["document-position"] =
            $this->nodes[$context]["document-position"] + 1;
        
        // Save the information about the node.
        $this->nodes[$fullpath]["name"]   = $name;
        $this->nodes[$fullpath]["text"]   = "";
        $this->nodes[$fullpath]["parent"] = $context;
        
        // Add this element to the element count array.
        if ( !$this->nodes[$context]["children"][$name] )
        {
            // Set the default name.
            $this->nodes[$context]["children"][$name] = 1;
        }
        else
        {
            // Calculate the name.
            $this->nodes[$context]["children"][$name] =
                $this->nodes[$context]["children"][$name] + 1;
        }
        
				if( $value != "" && is_array($attr_arr) )
				{
					$this->set_attributes($fullpath, $attr_arr);
					if( $attr_arr["urlencode"] == "1" )
						$value = urlencode( $value);
				}
				if( $value != "" )
				{
					$this->set_content($fullpath, $value);
				}

        // Return the path of the new node.
        return $fullpath;
    }
    function remove_node ( $node )
    {
        // Check whether the node is an attribute node.
        if ( preg_match("/attribute::/", $node) )
        {
            // Get the path to the attribute node's parent.
            $parent = $this->prestr($node, "/attribute::");
            
            // Get the name of the attribute.
            $attribute = $this->afterstr($node, "/attribute::");
            
            // Check whether the attribute exists.
            if ( isset($this->nodes[$parent]["attributes"][$attribute]) )
            {
                // Create a new array.
                $new = array();
                
                // Run through the existing attributes.
                foreach ( $this->nodes[$parent]["attributes"]
                    as $key => $value )
                {
                    // Check whether it's the attribute to remove.
                    if ( $key != $attribute )
                    {
                        // Add it to the new array again.
                        $new[$key] = $value;
                    }
                }
                
                // Save the new attributes.
                $this->nodes[$parent]["attributes"] = $new;
            }
        }
        else
        {
            // Create an associative array, which contains information about
            // all nodes that required to be renamed.
            $rename = array();
            
            // Get the name, the parent and the siblings of current node.
            $name     = $this->nodes[$node]["name"];
            $parent   = $this->nodes[$node]["parent"];
            $siblings = $this->nodes[$parent]["children"][$name];
            
            // Decrease the number of children.
            $this->nodes[$parent]["children"][$name]--;
            
            // Create a counter for renumbering the siblings.
            $counter = 1;
            
            // Now run through the siblings.
            for ( $i = 1; $i <= $siblings; $i++ )
            {
                // Create the name of the sibling.
                $sibling = $parent."/".$name."[".$i."]";
                
                // Check whether it's the name of the current node.
                if ( $sibling != $node )
                {
                    // Create the new name for the sibling.
                    $new = $parent."/".$name."[".$counter."]";
                    
                    // Increase the counter.
                    $counter++;
                    
                    // Add the old and the new name to the list of nodes
                    // to be renamed.
                    $rename[$sibling] = $new;
                }
            }
            
            // Create an array for saving the new node-list.
            $nodes = array();
            
            // Now run through through the existing nodes.
            foreach ( $this->nodes as $name => $values )
            {
                // Check the position of the path of the node to be deleted
                // in the path of the current node.
                $position = strpos($name, $node);

                // Check whether it's not the node to be deleted.
                if ( $position === false )
                {
                    // Run through the array of nodes to be renamed.
                    foreach ( $rename as $old => $new )
                    {
                        // Check whether this node and it's parent requires to
                        // be renamed.
                        $name             = str_replace($old, $new, $name);
                        $values["parent"] = str_replace($old, $new,
                            $values["parent"]);
                    }
                    
                    // Add the node to the list of nodes.
                    $nodes[$name] = $values;
                }
            }
            
            // Save the new array of nodes.
            $this->nodes = $nodes;
        }
    }
    function add_content ( $path, $value )
    {
        // Check whether it's an attribute node.
        if ( preg_match("/attribute::/", $path) )
        {
            // Get the path to the attribute node's parent.
            $parent = $this->prestr($path, "/attribute::");
            
            // Get the parent node.
            $parent = $this->nodes[$parent];
            
            // Get the name of the attribute.
            $attribute = $this->afterstr($path, "/attribute::");
            
            // Set the attribute.
            $parent["attributes"][$attribute] .= $value;
        }
        else
        {
            // Set the character data of the node.
            $this->nodes[$path]["text"] .= $value;
        }
    }
    function set_content ( $path, $value )
    {
        // Check whether it's an attribute node.
        if ( preg_match("/attribute::/", $path) )
        {
            // Get the path to the attribute node's parent.
            $parent = $this->prestr($path, "/attribute::");
            
            // Get the parent node.
            $parent = $this->nodes[$parent];
            
            // Get the name of the attribute.
            $attribute = $this->afterstr($path, "/attribute::");
            
            // Set the attribute.
            $parent["attributes"][$attribute] = $value;
        }
        else
        {
            // Set the character data of the node.
            $this->nodes[$path]["text"] = strtr($value, $this->entities);
        }
    }
    function get_content ( $path )
    {
        // Check whether it's an attribute node.
        if ( preg_match("/attribute::/", $path) )
        {
            // Get the path to the attribute node's parent.
            $parent = $this->prestr($path, "/attribute::");
            
            // Get the parent node.
            $parent = $this->nodes[$parent];
            
            // Get the name of the attribute.
            $attribute = $this->afterstr($path, "/attribute::");
            
            // Get the attribute.
            $attribute = $parent["attributes"][$attribute];
            
            // Return the value of the attribute.
            return $attribute;
        }
        else
        {
            // Return the cdata of the node.
            return stripslashes($this->nodes[$path]["text"]);
        }
    }
    function add_attributes ( $path, $attributes )
    {
        // Add the attributes to the node.
        $this->nodes[$path]["attributes"] = array_merge($attributes,
            $this->nodes[$path]["attributes"]);
    }
    function set_attributes ( $path, $attributes )
    {
        // Set the attributes of the node.
        $this->nodes[$path]["attributes"] = $attributes;
    }
    function get_attributes ( $path )
    {
        // Return the attributes of the node.
        return $this->nodes[$path]["attributes"];
    }
    function get_name ( $path )
    {
        // Return the name of the node.
        return $this->nodes[$path]["name"];
    }
    function evaluate ( $path, $context = "" )
    {
        // Remove slashes and quote signs.
        $path = stripslashes($path);
        $path = str_replace("\"", "", $path);
        $path = str_replace("'", "", $path);
        
        // Split the paths into different paths.
        $paths = $this->split_paths($path);
        
        // Create an empty set to save the result.
        $result = array();
        
        // Run through all paths.
        foreach ( $paths as $path )
        {
            // Trim the path.
            $path = trim($path);
            
            // Save the current path.
            $this->xpath = $path;
        
            // Convert all entities.
            $path = strtr($path, array_flip($this->entities));
        
            // Split the path at every slash.
            $steps = $this->split_steps($path);
        
            // Check whether the first element is empty.
            if ( empty($steps[0]) )
            {
                // Remove the first and empty element.
                array_shift($steps);
            }
        
            // Start to evaluate the steps.
            $nodes = $this->evaluate_step($context, $steps);
        
            // Remove duplicated nodes.
            $nodes = array_unique($nodes);
            
            // Add the nodes to the result set.
            $result = array_merge($result, $nodes);
        }
        
        // Return the result.
        return $result;
    }
    function handle_start_element ( $parser, $name, $attributes )
    {
        // Add a node.
        $this->path = $this->add_node($this->path, $name);
        
        // Set the attributes.
				// Xpath�� �Ȱ����´�. �Ѵ��� ������ �ߴ�!!
				// modifyed by ddaemiri, 2007.09.03
        // $this->set_attributes($this->path, $attributes);

				// add array, added by ddaemiri, 2007.09.03
				$arr = preg_split( "/[\/]+/", $this->path, -1, PREG_SPLIT_NO_EMPTY  );
				$this->xml_node[$arr[count($arr)-1]]["attr"] = $attributes;
    }
    function handle_end_element ( $parser, $name )
    {
        // Jump back to the parent element.
        $this->path = substr($this->path, 0, strrpos($this->path, "/"));
    }
    function handle_character_data ( $parser, $text )
    {
        // Replace entities.
        $text = strtr($text, $this->entities);
        
        // Save the text.
				// Xpath�� �Ȱ����´�. �Ѵ��� ������ �ߴ�!!
				// modifyed by ddaemiri, 2007.09.03
        //$this->add_content($this->path, addslashes(trim($text)));
			
				// add array, added by ddaemiri, 2007.09.03
				$arr = preg_split( "/[\/]+/", $this->path, -1, PREG_SPLIT_NO_EMPTY  );
				//edited by ddaemiri. libexpat�� \n�� �и��ڷ� �ν�
				//$this->xml_node[$arr[count($arr)-1]]["text"] = addslashes(trim($text));
				$this->xml_node[$arr[count($arr)-1]]["text"] = $this->xml_node[$arr[count($arr)-1]]["text"].addslashes(trim($text));
    }
    function split_paths ( $expression )
    {
        // Create an empty array.
        $paths = array();
        
        // Save the position of the slash.
        $position = -1;
        
        // Run through the expression.
        do
        {
            // Search for a slash.
            $position = $this->search_string($expression, "|");
            
            // Check whether a | was found.
            if ( $position >= 0 )
            {
                // Get the left part of the expression.
                $left  = substr($expression, 0, $position);
                $right = substr($expression, $position + 1);
                
                // Add the left value to the steps.
                $paths[] = $left;
                
                // Reduce the expression to the right part.
                $expression = $right;
            }
        }
        while ( $position > -1 );
        
        // Add the remaing expression to the list of steps.
        $paths[] = $expression;
        
        // Return the steps.
        return $paths;
    }
    function split_steps ( $expression )
    {
        // Create an empty array.
        $steps = array();
        
        // Replace a double slashes, because they'll cause problems otherwise.
        $expression = str_replace("//@", "/descendant::*/@", $expression);
        $expression = str_replace("//", "/descendant::", $expression);
        
        // Save the position of the slash.
        $position = -1;
        
        // Run through the expression.
        do
        {
            // Search for a slash.
            $position = $this->search_string($expression, "/");
            
            // Check whether a slash was found.
            if ( $position >= 0 )
            {
                // Get the left part of the expression.
                $left  = substr($expression, 0, $position);
                $right = substr($expression, $position + 1);
                
                // Add the left value to the steps.
                $steps[] = $left;
                
                // Reduce the expression to the right part.
                $expression = $right;
            }
        }
        while ( $position > -1 );
        
        // Add the remaing expression to the list of steps.
        $steps[] = $expression;
        
        // Return the steps.
        return $steps;
    }
    function get_axis ( $step, $node )
    {
        // Create an array to save the axis information.
        $axis = array(
            "axis"      => "",
            "node-test" => "",
            "predicate" => array()
        );
        
        // Check whether there are predicates.
        if ( preg_match("/\[/", $step) )
        {
            // Get the predicates.
            $predicates = substr($step, strpos($step, "["));
            
            // Reduce the step.
            $step = $this->prestr($step, "[");
            
            // Try to split the predicates.
            $predicates = str_replace("][", "]|[", $predicates);
            $predicates = explode("|", $predicates);
            
            // Run through all predicates.
            foreach ( $predicates as $predicate )
            {
                // Remove the brackets.
                $predicate = substr($predicate, 1, strlen($predicate) - 2);
                
                // Add the predicate to the list of predicates.
                $axis["predicate"][] = $predicate;
            }
        }
        
        // Check whether the axis is given in plain text.
        if ( $this->search_string($step, "::") > -1 )
        {
            // Split the step to extract axis and node-test.
            $axis["axis"]      = $this->prestr($step, "::");
            $axis["node-test"] = $this->afterstr($step, "::");
        }
        else
        {
            // Check whether the step is empty.
            if ( empty($step) )
            {
                // Set it to the default value.
                $step = ".";
            }
            
            // Check whether is an abbreviated syntax.
            if ( $step == "*" )
            {
                // Use the child axis and select all children.
                $axis["axis"]      = "child";
                $axis["node-test"] = "*";
            }
            //elseif ( ereg("\(", $step) )
            //elseif ( preg_match("\(", $step) )
            elseif ( preg_match("/\(/", $step) )
            {
                // Check whether it's a function.
                if ( $this->is_function($this->prestr($step, "(")) )
                {
                    // Get the position of the first bracket.
                    $start = strpos($step, "(");
                    $end   = strpos($step, ")", $start);
                    
                    // Get everything before, between and after the brackets.
                    $before  = substr($step, 0, $start);
                    $between = substr($step, $start + 1, $end - $start - 1);
                    $after   = substr($step, $end + 1);
                    
                    // Trim each string.
                    $before  = trim($before);
                    $between = trim($between);
                    $after   = trim($after);
                    
                    // Save the evaluated function.
                    $axis["axis"]      = "function";
                    $axis["node-test"] = $this->evaluate_function($before,
                        $between, $node);
                }
                else
                {
                    // Use the child axis and a function.
                    $axis["axis"]      = "child";
                    $axis["node-test"] = $step;
                }
            }
            //elseif ( eregi("^@", $step) )
            //elseif ( preg_match("^@/i", $step) )
            elseif ( preg_match("/^@/i", $step) )
            {
                // Use the attribute axis and select the attribute.
                $axis["axis"]      = "attribute";
                $axis["node-test"] = substr($step, 1);
            }
            //elseif ( eregi("\]$", $step) )
            //elseif ( preg_match("\]$/i", $step) )
            elseif ( preg_match("/\]$/i", $step) )
            {
                // Use the child axis and select a position.
                $axis["axis"]      = "child";
                $axis["node-test"] = substr($step, strpos($step, "["));
            }
            elseif ( $step == "." )
            {
                // Select the self axis.
                $axis["axis"]      = "self";
                $axis["node-test"] = "*";
            }
            elseif ( $step == ".." )
            {
                // Select the parent axis.
                $axis["axis"]      = "parent";
                $axis["node-test"] = "*";
            }
            //elseif ( ereg("^[a-zA-Z0-9\-_]+$", $step) )
            //elseif ( preg_match("^[a-zA-Z0-9\-_]+$", $step) )
            elseif ( preg_match("/^[a-zA-Z0-9\-_]+$/", $step) )
            {
                // Select the child axis and the child.
                $axis["axis"]      = "child";
                $axis["node-test"] = $step;
            }
            else
            {
                // Use the child axis and a name.
                $axis["axis"]      = "child";
                $axis["node-test"] = $step;
            }
        }

        // Check whether it's a valid axis.
        if ( !in_array($axis["axis"], array_merge($this->axes,
            array("function"))) )
        {
            // Display an error message.
            $this->display_error("While parsing an XPath expression, in ".
                "the step \"%s\" the invalid axis \"%s\" was found.",
                str_replace($step, "<b>".$step."</b>", $this->xpath),#
                $axis["axis"]);  
        }
        
        // Return the axis information.
        return $axis;
    }
    function search_string ( $term, $expression )
    {
        // Create a new counter for the brackets.
        $brackets = 0;
        
        // Run through the string.
        for ( $i = 0; $i < strlen($term); $i++ )
        {
            // Get the character at the position of the string.
            $character = substr($term, $i, 1);
            
            // Check whether it's a breacket.
            if ( ( $character == "(" ) || ( $character == "[" ) )
            {
                // Increase the number of brackets.
                $brackets++;
            }
            elseif ( ( $character == ")" ) || ( $character == "]" ) )
            {
                // Decrease the number of brackets.
                $brackets--;
            }
            elseif ( $brackets == 0 )
            {
                // Check whether we can find the expression at this index.
                if ( substr($term, $i, strlen($expression)) == $expression )
                {
                    // Return the current index.
                    return $i;
                }
            }
        }
        
        // Check whether we had a valid number of brackets.
        if ( $brackets != 0 )
        {
            // Display an error message.
            $this->display_error("While parsing an XPath expression, in the ".
                "predicate \"%s\", there was an invalid number of brackets.",
                str_replace($term, "<b>".$term."</b>", $this->xpath));
        }

        // Nothing was found.
        return (-1);
    }
    function is_function ( $expression )
    {
        // Check whether it's in the list of supported functions.
        if ( in_array($expression, $this->functions) )
        {
            // It's a function.
            return true;
        }
        else
        {
            // It's not a function.
            return false;
        }
    }
    function evaluate_step ( $context, $steps )
    {
        // Create an empty array for saving the nodes found.
        $nodes = array();

        // Check whether the context is an array of contexts.
        if ( is_array($context) )
        {
            // Run through the array.
            foreach ( $context as $path )
            {
                // Call this method for this single path.
                $nodes = array_merge($nodes,
                    $this->evaluate_step($path, $steps));
            }
        }
        else
        {
            // Get this step.
            $step = array_shift($steps);
            
            // Create an array to save the new contexts.
            $contexts = array();
            
            // Get the axis of the current step.
            $axis = $this->get_axis($step, $context);
            
            // Check whether it's a function.
            if ( $axis["axis"] == "function" )
            {
                // Check whether an array was return by the function.
                if ( is_array($axis["node-test"]) )
                {
                    // Add the results to the list of contexts.
                    $contexts = array_merge($contexts, $axis["node-test"]);
                }
                else
                {
                    // Add the result to the list of contexts.
                    $contexts[] = $axis["node-test"];
                }
            }
            else
            {
                // Create the name of the method.
                $method = "handle_axis_".str_replace("-", "_", $axis["axis"]);
            
                // Check whether the axis handler is defined.
                if ( !method_exists($this, $method) )
                {
                    // Display an error message.
                    $this->display_error("While parsing an XPath expression, ".
                        "the axis \"%s\" could not be handled, because this ".
                        "version does not support this axis.", $axis["axis"]);
                }
            
                // Perform an axis action.
                $contexts = call_user_method($method, $this, $axis, $context);
            
                // Check whether there are predicates.
                if ( count($axis["predicate"]) > 0 )
                {
                    // Check whether each node fits the predicates.
                    $contexts = $this->check_predicates($contexts,
                        $axis["predicate"]);
                }
            }
            
            // Check whether there are more steps left.
            if ( count($steps) > 0 )
            {
                // Continue the evaluation of the next steps.
                $nodes = $this->evaluate_step($contexts, $steps);
            }
            else
            {
                // Save the found contexts.
                $nodes = $contexts;
            }
        }
        
        // Return the nodes found.
        return $nodes;
    }
    function evaluate_function ( $function, $arguments, $node )
    {
        // Remove whitespaces.
        $function  = trim($function);
        $arguments = trim($arguments);

        // Create the name of the function handling function.
        $method = "handle_function_".str_replace("-", "_", $function);
        
        // Check whether the function handling function is available.
        if ( !method_exists($this, $method) )
        {
            // Display an error message.
            $this->display_error("While parsing an XPath expression, ".
                "the function \"%s\" could not be handled, because this ".
                "version does not support this function.", $function);
        }
        
        // Return the result of the function.
        return call_user_method($method, $this, $node, $arguments);
    }
    function evaluate_predicate ( $node, $predicate )
    {
        // Set the default position and the type of the operator.
        $position = 0;
        $operator = "";
        
        // Run through all operators and try to find them.
        foreach ( $this->operators as $expression )
        {
            // Check whether a position was already found.
            if ( $position <= 0 )
            {
                // Try to find the operator.
                $position = $this->search_string($predicate, $expression);
            
                // Check whether a operator was found.
                if ( $position > 0 )
                {
                    // Save the operator.
                    $operator = $expression;
                    
                    // Check whether it's the equal operator.
                    if ( $operator == "=" )
                    {
                        // Also look for other operators containing the
                        // equal sign.
                        if ( $this->search_string($predicate, "!=") ==
                            ( $position - 1 ) )
                        {
                            // Get the new position.
                            $position = $this->search_string($predicate, "!=");
                            
                            // Save the new operator.
                            $operator = "!=";
                        }
                        if ( $this->search_string($predicate, "<=") ==
                            ( $position - 1 ) )
                        {
                            // Get the new position.
                            $position = $this->search_string($predicate, "<=");
                            
                            // Save the new operator.
                            $operator = "<=";
                        }
                        if ( $this->search_string($predicate, ">=") ==
                            ( $position - 1 ) )
                        {
                            // Get the new position.
                            $position = $this->search_string($predicate, ">=");
                            
                            // Save the new operator.
                            $operator = ">=";
                        }
                    }
                }
            }
        }
        
        // Check whether the operator is a - sign.
        if ( $operator == "-" )
        {
            // Check whether it's not a function containing a - in its name.
            foreach ( $this->functions as $function )
            {
                // Check whether there's a - sign in the function name.
                //if ( ereg("-", $function) )
                //if ( preg_match("-", $function) )
                if ( preg_match("/-/", $function) )
                {
                    // Get the position of the - in the function name.
                    $sign = strpos($function, "-");
                    
                    // Extract a substring from the predicate.
                    $sub = substr($predicate, $position - $sign,
                        strlen($function));
                        
                    // Check whether it's the function.
                    if ( $sub == $function )
                    {
                        // Don't use the operator.
                        $operator = "";
                        $position = -1;
                    }
                }
            }
        }
        elseif ( $operator == "*" )
        {
            // Get some substrings.
            $character = substr($predicate, $position - 1, 1);
            $attribute = substr($predicate, $position - 11, 11);
            
            // Check whether it's an attribute selection.
            if ( ( $character == "@" ) || ( $attribute == "attribute::" ) )
            {
                // Don't use the operator.
                $operator = "";
                $position = -1;
            }
        }
        
        // Check whether an operator was found.        
        if ( $position > 0 )
        {
            // Get the left and the right part of the expression.
            $left  = substr($predicate, 0, $position);
            $right = substr($predicate, $position + strlen($operator));
            
            // Remove whitespaces.
            $left  = trim($left);
            $right = trim($right);
            
            // Evaluate the left and the right part.
            $left  = $this->evaluate_predicate($node, $left);
            $right = $this->evaluate_predicate($node, $right);
            
            // Check the kind of operator.
            switch ( $operator )
            {
                case " or ":
                    // Return the two results connected by an "or".
                    return ( $left or $right );
                
                case " and ":
                    // Return the two results connected by an "and".
                    return ( $left and $right );
                
                case "=":
                    // Compare the two results.
                    return ( $left == $right );
                    
                case "!=":
                    // Check whether the two results are not equal.
                    return ( $left != $right );
                    
                case "<=":
                    // Compare the two results.
                    return ( $left <= $right );
                    
                case "<":
                    // Compare the two results.
                    return ( $left < $right );
                
                case ">=":
                    // Compare the two results.
                    return ( $left >= $right );
                    
                case ">":
                    // Compare the two results.
                    return ( $left > $right );
                    
                case "+":
                    // Return the result by adding one result to the other.
                    return ( $left + $right );
                
                case "-":
                    // Return the result by decrease one result by the other.
                    return ( $left - $right );
                
                case "*":
                    // Return a multiplication of the two results.
                    return ( $left * $right );
                    
                case " div ":
                    // Return a division of the two results.
                    if ( $right == 0 )
                    {
                        // Display an error message.
                        $this->display_error("While parsing an XPath ".
                            "predicate, a error due a division by zero ".
                            "occured.");
                    }
                    else
                    {
                        // Return the result of the division.
                        return ( $left / $right );
                    }
                    break;
                
                case " mod ":
                    // Return a modulo of the two results.
                    return ( $left % $right );
            }
        }
        
        // Check whether the predicate is a function.
        //if ( ereg("\(", $predicate) )
        //if ( preg_match("\(", $predicate) )
        if ( preg_match("/\(/", $predicate) )
        {
            // Get the position of the first bracket.
            $start = strpos($predicate, "(");
            $end   = strpos($predicate, ")", $start);
            
            // Get everything before, between and after the brackets.
            $before  = substr($predicate, 0, $start);
            $between = substr($predicate, $start + 1, $end - $start - 1);
            $after   = substr($predicate, $end + 1);
            
            // Trim each string.
            $before  = trim($before);
            $between = trim($between);
            $after   = trim($after);
            
            // Check whether there's something after the bracket.
            if ( !empty($after) )
            {
                // Display an error message.
                $this->display_error("While parsing an XPath expression ".
                    "there was found an error in the predicate \"%s\", ".
                    "because after a closing bracket there was found ".
                    "something unknown.", str_replace($predicate,
                    "<b>".$predicate."</b>", $this->xpath));
            }
            
            // Check whether it's a function.
            if ( empty($before) && empty($after) )
            {
                // Evaluate the content of the brackets.
                return $this->evaluate_predicate($node, $between);
            }
            elseif ( $this->is_function($before) )
            {
                // Return the evaluated function.
                return $this->evaluate_function($before, $between, $node);
            }
            else
            {
                // Display an error message.
                $this->display_error("While parsing a predicate in an XPath ".
                    "expression, a function \"%s\" was found, which is not ".
                    "yet supported by the parser.", str_replace($before,
                    "<b>".$before."</b>", $this->xpath));
            }
        }
        
        // Check whether the predicate is just a digit.
        //if ( ereg("^[0-9]+(\.[0-9]+)?$", $predicate) || ereg("^\.[0-9]+$", $predicate) )
        //if ( preg_match("^[0-9]+(\.[0-9]+)?$", $predicate) || preg_match("^\.[0-9]+$", $predicate) )
        if ( preg_match("/^[0-9]+(\.[0-9]+)?$/", $predicate) || preg_match("/^\.[0-9]+$/", $predicate) )
        {
            // Return the value of the digit.
            return doubleval($predicate);
        }
        
        // Check whether it's an XPath expression.
        $result = $this->evaluate($predicate, $node);
        if ( count($result) > 0 )
        {
            // Convert the array.
            $result = explode("|", implode("|", $result));
            
            // Get the value of the first result.
            $value = $this->get_content($result[0]);
            
            // Return the value.
            return $value;
        }
        
        // Return the predicate as a string.
        return $predicate;
    }
    function check_predicates ( $nodes, $predicates )
    {
        // Create an empty set of nodes.
        $result = array();
        
        // Run through all nodes.
        foreach ( $nodes as $node )
        {
            // Create a variable whether to add this node to the node-set.
            $add = true;
            
            // Run through all predicates.
            foreach ( $predicates as $predicate )
            {
                // Check whether the predicate is just an number.
                //if ( ereg("^[0-9]+$", $predicate) )
                //if ( preg_match("^[0-9]+$", $predicate) )
                if ( preg_match("/^[0-9]+$/", $predicate) )
                {
                    // Enhance the predicate.
                    $predicate .= "=position()";
                }
                
                // Do the predicate check.
                $check = $this->evaluate_predicate($node, $predicate);
                
                // Check whether it's a string.
                if ( is_string($check) && ( ( $check == "" ) ||
                    ( $check == $predicate ) ) )
                {
                    // Set the result to false.
                    $check = false;
                }
                
                // Check whether it's an integer.
                if ( is_int($check) )
                {
                    // Check whether it's the current position.
                    if ( $check == $this->handle_function_position($node, "") )
                    {
                        // Set it to true.
                        $check = true;
                    }
                    else
                    {
                        // Set it to false.
                        $check = false;
                    }
                }
                
                // Check whether the predicate is OK for this node.
                $add = $add && $check;
            }
            
            // Check whether to add this node to the node-set.
            if ( $add )
            {
                // Add the node to the node-set.
                $result[] = $node;
            }            
        }
        
        // Return the array of nodes.
        return $result;
    }
    function check_node_test ( $context, $node_test )
    {
        // Check whether it's a function.
        //if ( ereg("\(", $node_test) )
        if ( preg_match("/\(/", $node_test) )
        {
            // Get the type of function to use.
            $function = $this->prestr($node_test, "(");
            
            // Check whether the node fits the method.
            switch ( $function )
            {
                case "node":
                    // Add this node to the list of nodes.
                    return true;
                    
                case "text":
                    // Check whether the node has some text.
                    if ( !empty($this->nodes[$context]["text"]) )
                    {
                        // Add this node to the list of nodes.
                        return true;
                    }
                    break;
                    
                case "comment":
                    // Check whether the node has some comment.
                    if ( !empty($this->nodes[$context]["comment"]) )
                    {
                        // Add this node to the list of nodes.
                        return true;
                    }
                    break;
                
                case "processing-instruction":
                    // Get the literal argument.
                    $literal = $this->afterstr($axis["node-test"], "(");
                    
                    // Cut the literal.
                    $literal = substr($literal, 0, strlen($literal) - 1);
                    
                    // Check whether a literal was given.
                    if ( !empty($literal) )
                    {
                        // Check whether the node's processing instructions
                        // are matching the literals given.
                        if ( $this->nodes[$context]
                            ["processing-instructions"] == $literal )
                        {
                            // Add this node to the node-set.
                            return true;
                        }
                    }
                    else
                    {
                        // Check whether the node has processing
                        // instructions.
                        if ( !empty($this->nodes[$context]
                            ["processing-instructions"]) )
                        {
                            // Add this node to the node-set.
                            return true;
                        }
                    }
                    break;
                    
                default:
                    // Display an error message.
                    $this->display_error("While parsing an XPath ".
                        "expression there was found an undefined ".
                        "function called \"%s\".",
                        str_replace($function, "<b>".$function."</b>",
                        $this->xpath));
            }
        }
        elseif ( $node_test == "*" )
        {
            // Add this node to the node-set.
            return true;
        }
        //elseif ( ereg("^[a-zA-Z0-9\-_]+", $node_test) )
        //elseif ( preg_match("^[a-zA-Z0-9\-_]+", $node_test) )
        elseif ( preg_match("/^[a-zA-Z0-9\-_]+/", $node_test) )
        {
            // Check whether the node-test can be fulfilled.
            if ( $this->nodes[$context]["name"] == $node_test )
            {
                // Add this node to the node-set.
                return true;
            }
        }
        else
        {
            // Display an error message.
            $this->display_error("While parsing the XPath expression \"%s\" ".
                "an empty and therefore invalid node-test has been found.",
                $this->xpath);
        }
        
        // Don't add this context.
        return false;
    }
    function handle_axis_child ( $axis, $context )
    {
        // Create an empty node-set.
        $nodes = array();
        
        // Get a list of all children.
        $children = $this->nodes[$context]["children"];
        
        // Check whether there are children.
        if ( !empty($children) )
        {
            // Run through all children.
            foreach ( $children as $child_name => $child_position )
            {
                // Run through all childs with this name.
                for ( $i = 1; $i <= $child_position; $i++ )
                {
                    // Create the path of the child.
                    $child = $context."/".$child_name."[".$i."]";
                    
                    // Check whether 
                    if ( $this->check_node_test($child, $axis["node-test"]) )
                    {
                        // Add the child to the node-set.
                        $nodes[] = $child;
                    }
                }
            }
        }
        
        // Return the nodeset.
        return $nodes;
    }
    function handle_axis_parent ( $axis, $context )
    {
        // Create an empty node-set.
        $nodes = array();
        
        // Check whether the parent matches the node-test.
        if ( $this->check_node_test($this->nodes[$context]["parent"],
            $axis["node-test"]) )
        {
            // Add this node to the list of nodes.
            $nodes[] = $this->nodes[$context]["parent"];
        }
        
        // Return the nodeset.
        return $nodes;
    }
    function handle_axis_attribute ( $axis, $context )
    {
        // Create an empty node-set.
        $nodes = array();
        
        // Check whether all nodes should be selected.
        if ( $axis["node-test"] == "*" )
        {
            // Check whether there are attributes.
            if ( count($this->nodes[$context]["attributes"]) > 0 )
            {
                // Run through the attributes.
                foreach ( $this->nodes[$context]["attributes"] as
                    $key => $value )
                {
                    // Add this node to the node-set.
                    $nodes[] = $context."/attribute::".$key;
                }
            }
        }
        elseif ( !empty($this->nodes[$context]["attributes"]
            [$axis["node-test"]]) )
        {
            // Add this node to the node-set.
            $nodes[] = $context."/attribute::".$axis["node-test"];
        }
            
        // Return the nodeset.
        return $nodes;
    }
    function handle_axis_self ( $axis, $context )
    {
        // Create an empty node-set.
        $nodes = array();
        
        // Check whether the context match the node-test.
        if ( $this->check_node_test($context, $axis["node-test"]) )
        {
            // Add this node to the node-set.
            $nodes[] = $context;
        }

        // Return the nodeset.
        return $nodes;
    }
    function handle_axis_descendant ( $axis, $context )
    {
        // Create an empty node-set.
        $nodes = array();
        
        // Check whether the current node has children.
        if ( count($this->nodes[$context]["children"]) > 0 )
        {
            // Get a list of children.
            $children = $this->nodes[$context]["children"];
            
            // Run through all children.
            foreach ( $children as $child_name => $child_position )
            {
                // Run through all children of this name.
                for ( $i = 1; $i <= $child_position; $i++ )
                {
                    // Create the full path for the children.
                    $child = $context."/".$child_name."[".$i."]";
                    
                    // Check whether the child matches the node-test.
                    if ( $this->check_node_test($child, $axis["node-test"]) )
                    {
                        // Add the child to the list of nodes.
                        $nodes[] = $child;
                    }
                    
                    // Recurse to the next level.
                    $nodes = array_merge($nodes,
                        $this->handle_axis_descendant($axis, $child));
                }
            }
        }
        
        // Return the nodeset.
        return $nodes;
    }
    function handle_axis_ancestor ( $axis, $context )
    {
        // Create an empty node-set.
        $nodes = array();
        
        // Get the parent of the current node.
        $parent = $this->nodes[$context]["parent"];
        
        // Check whether the parent isn't empty.
        if ( !empty($parent) )
        {
            // Check whether the parent matches the node-test.
            if ( $this->check_node_test($parent, $axis["node-test"]) )
            {
                // Add the parent to the list of nodes.
                $nodes[] = $parent;
            }
            
            // Handle all other ancestors.
            $nodes = array_merge($nodes,
                $this->handle_axis_ancestor($axis, $parent));
        }
        
        // Return the nodeset.
        return $nodes;
    }
    function handle_axis_namespace ( $axis, $context )
    {
        // Create an empty node-set.
        $nodes = array();
        
        // Check whether all nodes should be selected.
        if ( !empty($this->nodes[$context]["namespace"]) )
        {
            // Add this node to the node-set.
            $nodes[] = $context;
        }
            
        // Return the nodeset.
        return $nodes;
    }
    function handle_axis_following ( $axis, $context )
    {
        // Create an empty node-set.
        $nodes = array();
        
        // Get the current document position.
        $position = $this->nodes[$context]["document-position"];
        
        // Create a flag, whether we already found the context node.
        $found = false;
        
        // Run through all nodes of the document.
        foreach ( $this->nodes as $node => $data )
        {
            // Check whether the context node has already been found.
            if ( $found )
            {
                // Check whether the position is correct.
                if ( $this->nodes[$node]["document-position"] == $position )
                {
                    // Check whether the node fits the node-test.
                    if ( $this->check_node_test($node, $axis["node-test"]) )
                    {
                        // Add the node to the list of nodes.
                        $nodes[] = $node;
                    }
                }
            }
            
            // Check whether this is the context node.
            if ( $node == $context )
            {
                // After this we'll look for more nodes.
                $found = true;
            }
        }
            
        // Return the nodeset.
        return $nodes;
    }
    function handle_axis_preceding ( $axis, $context )
    {
        // Create an empty node-set.
        $nodes = array();
        
        // Get the current document position.
        $position = $this->nodes[$context]["document-position"];
        
        // Create a flag, whether we already found the context node.
        $found = true;
        
        // Run through all nodes of the document.
        foreach ( $this->nodes as $node => $data )
        {
            // Check whether this is the context node.
            if ( $node == $context )
            {
                // After this we won't look for more nodes.
                $found = false;
            }
            
            // Check whether the context node has already been found.
            if ( $found )
            {
                // Check whether the position is correct.
                if ( $this->nodes[$node]["document-position"] == $position )
                {
                    // Check whether the node fits the node-test.
                    if ( $this->check_node_test($node, $axis["node-test"]) )
                    {
                        // Add the node to the list of nodes.
                        $nodes[] = $node;
                    }
                }
            }
        }
            
        // Return the nodeset.
        return $nodes;
    }
    function handle_axis_following_sibling ( $axis, $context )
    {
        // Create an empty node-set.
        $nodes = array();
        
        // Get all children from the parent.
        $siblings = $this->handle_axis_child($axis,
            $this->nodes[$context]["parent"]);
        
        // Create a flag whether the context node was already found.
        $found = false;
        
        // Run through all siblings.
        foreach ( $siblings as $sibling )
        {
            // Check whether the context node was already found.
            if ( $found )
            {
                // Check whether the sibling is a real sibling.
                if ( $this->nodes[$sibling]["name"] ==
                    $this->nodes[$context]["name"] )
                {
                    // Check whether the sibling matches the node-test.
                    if ( $this->check_node_test($sibling, $axis["node-test"]) )
                    {
                        // Add the sibling to the list of nodes.
                        $nodes[] = $sibling;
                    }
                }
            }
            
            // Check whether this is the context node.
            if ( $sibling == $context )
            {
                // Continue looking for other siblings.
                $found = true;
            }
        }
            
        // Return the nodeset.
        return $nodes;
    }
    function handle_axis_preceding_sibling ( $axis, $context )
    {
        // Create an empty node-set.
        $nodes = array();
        
        // Get all children from the parent.
        $siblings = $this->handle_axis_child($axis,
            $this->nodes[$context]["parent"]);
        
        // Create a flag whether the context node was already found.
        $found = true;
        
        // Run through all siblings.
        foreach ( $siblings as $sibling )
        {
            // Check whether this is the context node.
            if ( $sibling == $context )
            {
                // Don't continue looking for other siblings.
                $found = false;
            }
            
            // Check whether the context node was already found.
            if ( $found )
            {
                // Check whether the sibling is a real sibling.
                if ( $this->nodes[$sibling]["name"] ==
                    $this->nodes[$context]["name"] )
                {
                    // Check whether the sibling matches the node-test.
                    if ( $this->check_node_test($sibling, $axis["node-test"]) )
                    {
                        // Add the sibling to the list of nodes.
                        $nodes[] = $sibling;
                    }
                }
            }
        }
            
        // Return the nodeset.
        return $nodes;
    }
    function handle_axis_descendant_or_self ( $axis, $context )
    {
        // Create an empty node-set.
        $nodes = array();
        
        // Read the nodes.
        $nodes = array_merge(
            $this->handle_axis_descendant($axis, $context),
            $this->handle_axis_self($axis, $context));
        
        // Return the nodeset.
        return $nodes;
    }
    function handle_axis_ancestor_or_self ( $axis, $context )
    {
        // Create an empty node-set.
        $nodes = array();
        
        // Read the nodes.
        $nodes = array_merge(
            $this->handle_axis_ancestor($axis, $context),
            $this->handle_axis_self($axis, $context));
        
        // Return the nodeset.
        return $nodes;
    }
    function handle_function_last ( $node, $arguments )
    {
        // Calculate the size of the context.
        $parent   = $this->nodes[$node]["parent"];
        $children = $this->nodes[$parent]["children"];
        $context  = $children[$this->nodes[$node]["name"]];

        // Return the size.
        return $context;
    }
    function handle_function_position ( $node, $arguments )
    {
        // return the context-position.
        return $this->nodes[$node]["context-position"];
    }
    function handle_function_count ( $node, $arguments )
    {
        // Evaluate the argument of the method as an XPath and return
        // the number of results.
        return count($this->evaluate($arguments, $node));
    }
    function handle_function_id ( $node, $arguments )
    {
        // Trim the arguments.
        $arguments = trim($arguments);
        
        // Now split the arguments.
        $arguments = explode(" ", $arguments);
        
        // Check whether 
        
        // Create a list of nodes.
        $nodes = array();
        
        // Run through all document node.
        foreach ( $this->nodes as $node => $position )
        {
            // Check whether the node has the ID we're looking for.
            if ( in_array($this->nodes[$node]["attributes"]["id"],
                $arguments) )
            {
                // Add this node to the list of nodes.
                $nodes[] = $node;
            }
        }
        
        // Return the list of nodes.
        return $nodes;
    }
    function handle_function_name ( $node, $arguments )
    {
        // Return the name of the node.
        return $this->nodes[$node]["name"];
    }
    function handle_function_string ( $node, $arguments )
    {
        // Check what type of parameter is given
        //if ( ereg("^[0-9]+(\.[0-9]+)?$", $arguments) || ereg("^\.[0-9]+$", $arguments) )
        //if ( preg_match("^[0-9]+(\.[0-9]+)?$", $arguments) || preg_match("^\.[0-9]+$", $arguments) )
        if ( preg_match("/^[0-9]+(\.[0-9]+)?$/", $arguments) || preg_match("/^\.[0-9]+$/", $arguments) )
        {
            // Convert the digits to a number.
            $number = doubleval($arguments);
                
            // Return the number.
            return strval($number);
        }
        elseif ( is_bool($arguments) )
        {
            // Check whether it's true.
            if ( $arguments == true )
            {
                // Return true as a string.
                return "true";
            }
            else
            {
                // Return false as a string.
                return "false";
            }
        }
        elseif ( !empty($arguments) )
        {
            // Use the argument as an XPath.
            $result = $this->evaluate($arguments, $node);
                
            // Get the first argument.
            $result = explode("|", implode("|", $result));
                
            // Return the first result as a string.
            return $result[0];
        }
        elseif ( empty($arguments) )
        {
            // Return the current node.
            return $node;
        }
        else
        {
            // Return an empty string.
            return "";
        }
    }
    function handle_function_concat ( $node, $arguments )
    {
        // Split the arguments.
        $arguments = explode(",", $arguments);
            
        // Run through each argument and evaluate it.
        for ( $i = 0; $i < sizeof($arguments); $i++ )
        {
            // Trim each argument.
            $arguments[$i] = trim($arguments[$i]);
                
            // Evaluate it.
            $arguments[$i] = $this->evaluate_predicate($node, $arguments[$i]);
        }
            
        // Put the string together.
        $arguments = implode("", $arguments);
            
        // Return the string.
        return $arguments;
    }
    function handle_function_starts_with ( $node, $arguments )
    {
        // Get the arguments.
        $first  = trim($this->prestr($arguments, ","));
        $second = trim($this->afterstr($arguments, ","));
            
        // Evaluate each argument.
        $first  = $this->evaluate_predicate($node, $first);
        $second = $this->evaluate_predicate($node, $second);
            
        // Check whether the first string starts with the second one.
        //if ( ereg("^".$second, $first) )
        //if ( preg_match("^".$second, $first) )
        if ( preg_match("/^".$second."/", $first) )
        {
            // Return true.
            return true;
        }
        else
        {
            // Return false.
            return false;
        }
    }
    function handle_function_contains ( $node, $arguments )
    {
        // Get the arguments.
        $first  = trim($this->prestr($arguments, ","));
        $second = trim($this->afterstr($arguments, ","));
            
        // Evaluate each argument.
        $first  = $this->evaluate_predicate($node, $first);
        $second = $this->evaluate_predicate($node, $second);
            
        // Check whether the first string starts with the second one.
        //if ( ereg($second, $first) )
        //if ( preg_match($second, $first) )
        if ( preg_match("/^".$second."/", $first) )
        {
            // Return true.
            return true;
        }
        else
        {
            // Return false.
            return false;
        }
    }
    function handle_function_substring_before ( $node, $arguments )
    {
        // Get the arguments.
        $first  = trim($this->prestr($arguments, ","));
        $second = trim($this->afterstr($arguments, ","));
          
        // Evaluate each argument.
        $first  = $this->evaluate_predicate($node, $first);
        $second = $this->evaluate_predicate($node, $second);
            
        // Return the substring.
        return $this->prestr(strval($first), strval($second));
    }
    function handle_function_substring_after ( $node, $arguments )
    {
        // Get the arguments.
        $first  = trim($this->prestr($arguments, ","));
        $second = trim($this->afterstr($arguments, ","));
            
        // Evaluate each argument.
        $first  = $this->evaluate_predicate($node, $first);
        $second = $this->evaluate_predicate($node, $second);
            
        // Return the substring.
        return $this->afterstr(strval($first), strval($second));
    }
    function handle_function_substring ( $node, $arguments )
    {
        // Split the arguments.
        $arguments = explode(",", $arguments);
            
        // Run through all arguments.
        for ( $i = 0; $i < sizeof($arguments); $i++ )
        {
            // Trim the string.
            $arguments[$i] = trim($arguments[$i]);
                
            // Evaluate each argument.
            $arguments[$i] = $this->evaluate_predicate($node, $arguments[$i]);
        }
            
        // Check whether a third argument was given.
        if ( !empty($arguments[2]) )
        {
            // Return the substring.
            return substr(strval($arguments[0]), $arguments[1] - 1,
                $arguments[2]);
        }
        else
        {
            // Return the substring.
            return substr(strval($arguments[0]), $arguments[1] - 1);
        }
    }
    function handle_function_string_length ( $node, $arguments )
    {
        // Trim the argument.
        $arguments = trim($arguments);
            
        // Evaluate the argument.
        $arguments = $this->evaluate_predicate($node, $arguments);
            
        // Return the length of the string.
        return strlen(strval($arguments));
    }
    function handle_function_translate ( $node, $arguments )         
    {
        // Split the arguments.
        $arguments = explode(",", $arguments);
        
        // Run through all arguments.
        for ( $i = 0; $i < sizeof($arguments); $i++ )
        {
            // Trim the argument.
            $arguments[$i] = trim($arguments[$i]);
            
            // Evaluate the argument.
            $arguments[$i] = $this->evaluate_predicate($node, $arguments[$i]);
        }
            
        // Return the translated string.
        return strtr($arguments[0], $arguments[1], $arguments[2]);
    }
    function handle_function_boolean ( $node, $arguments )
    {
        // Trim the arguments.
        $arguments = trim($arguments);
        
        // Check what type of parameter is given
        //if ( ereg("^[0-9]+(\.[0-9]+)?$", $arguments) || ereg("^\.[0-9]+$", $arguments) )
        //if ( preg_match("^[0-9]+(\.[0-9]+)?$", $arguments) || preg_match("^\.[0-9]+$", $arguments) )
        if ( preg_match("/^[0-9]+(\.[0-9]+)?$/", $arguments) || preg_match("/^\.[0-9]+$/", $arguments) )
        {
            // Convert the digits to a number.
            $number = doubleval($arguments);
            
            // Check whether the number zero.
            if ( $number == 0 )
            {
                // Return false.
                return false;
            }
            else
            {
                // Return true.
                return true;
            }
        }
        elseif ( empty($arguments) )
        {
            // Sorry, there were no arguments.
            return false;
        }
        else
        {
            // Try to evaluate the argument as an XPath.
            $result = $this->evaluate($arguments, $node);
            
            // Check whether we found something.
            if ( count($result) > 0 )
            {
                // Return true.
                return true;
            }
            else
            {
                // Return false.
                return false;
            }
        }
    }
    function handle_function_not ( $node, $arguments )
    {
        // Trim the arguments.
        $arguments = trim($arguments);
        
        // Return the negative value of the content of the brackets.
        return !$this->evaluate_predicate($node, $arguments);
    }
    function handle_function_true ( $node, $arguments )
    {
        // Return true.
        return true;
    }
    function handle_function_false ( $node, $arguments )
    {
        // Return false.
        return false;
    }
    function handle_function_lang ( $node, $arguments )
    {
        // Trim the arguments.
        $arguments = trim($arguments);
        
        // Check whether the node has an language attribute.
        if ( empty($this->nodes[$node]["attributes"]["xml:lang"]) )
        {
            // Run through the ancestors.
            while ( !empty($node) )
            {
                // Select the parent node.
                $node = $this->nodes[$node]["parent"];
                
                // Check whether there's a language definition.
                if ( !empty($this->nodes[$node]["attributes"]["xml:lang"]) )
                {
                    // Check whether it's the language, the user asks for.
                    //if ( eregi("^".$arguments, $this->nodes[$node]
                    //    ["attributes"]["xml:lang"]) )
                    //if ( preg_match("^/i".$arguments, $this->nodes[$node]
                    if ( preg_match("/^".$arguments."/i", $this->nodes[$node]
                        ["attributes"]["xml:lang"]) )    
                    {
                        // Return true.
                        return true;
                    }
                    else
                    {
                        // Return false.
                        return false;
                    }
                }
            }
            
            // Return false.
            return false;
        }
        else
        {
            // Check whether it's the language, the user asks for.
            //if ( eregi("^".$arguments, $this->nodes[$node]["attributes"]
            //    ["xml:lang"]) )
            //if ( preg_match("^/i".$arguments, $this->nodes[$node]["attributes"]
            if ( preg_match("/^".$arguments."/i", $this->nodes[$node]["attributes"]
                ["xml:lang"]) )
            {
                // Return true.
                return true;
            }
            else
            {
                // Return false.
                return false;
            }
        }
    }
    function handle_function_number ( $node, $arguments )
    {
        // Check the type of argument.
        //if ( ereg("^[0-9]+(\.[0-9]+)?$", $arguments) ||
        //    ereg("^\.[0-9]+$", $arguments) )
        //if ( preg_match("^[0-9]+(\.[0-9]+)?$", $arguments) || preg_match("^\.[0-9]+$", $arguments) )
        if ( preg_match("/^[0-9]+(\.[0-9]+)?$/", $arguments) || preg_match("/^\.[0-9]+$/", $arguments) )
        {
            // Return the argument as a number.
            return doubleval($arguments);
        }
        elseif ( is_bool($arguments) )
        {
            // Check whether it's true.
            if ( $arguments == true )
            {
                // Return 1.
                return 1;
            }
            else
            {
                // Return 0.
                return 0;
            }
        }
    }
    function handle_function_sum ( $node, $arguments )
    {
        // Trim the arguments.
        $arguments = trim($arguments);
        
        // Evaluate the arguments as an XPath expression.
        $results = $this->evaluate($arguments, $node);
        
        // Create a variable to save the sum.
        $sum = 0;
        
        // Run through all results.
        foreach ( $results as $result )
        {
            // Get the value of the node.
            $result = $this->get_content($result);
            
            // Add it to the sum.
            $sum += doubleval($result);
        }
        
        // Return the sum.
        return $sum;
    }
    function handle_function_floor ( $node, $arguments )
    {
        // Trim the arguments.
        $arguments = trim($arguments);
        
        // Convert the arguments to a number.
        $arguments = doubleval($arguments);
        
        // Return the result
        return floor($arguments);
    }
    function handle_function_ceiling ( $node, $arguments )
    {
        // Trim the arguments.
        $arguments = trim($arguments);
        
        // Convert the arguments to a number.
        $arguments = doubleval($arguments);
        
        // Return the result
        return ceil($arguments);
    }
    function handle_function_round ( $node, $arguments )
    {
        // Trim the arguments.
        $arguments = trim($arguments);
        
        // Convert the arguments to a number.
        $arguments = doubleval($arguments);
        
        // Return the result
        return round($arguments);
    }
    function handle_function_text ( $node, $arguments )
    {
        // Return the character data of the node.
        return $this->nodes[$node]["text"];
    }
    function prestr ( $string, $delimiter )
    {
        // Return the substring.
		return substr($string, 0, strlen($string) - strlen(strstr($string,
            "$delimiter")));
	}
    function afterstr ( $string, $delimiter )
    {
        // Return the substring.
		return substr($string,
            strpos($string, $delimiter) + strlen($delimiter));
	}
    function display_error ( $message )
    {
			// Check whether more than one argument was given.
			if ( func_num_args() > 1 )
			{
				// Read all arguments.
				$arguments = func_get_args();
				
				// Create a new string for the inserting command.
				$command = "\$message = sprintf(\$message, ";
				
				// Run through the array of arguments.
				for ( $i = 1; $i < sizeof($arguments); $i++ )
				{
					// Add the number of the argument to the command.
					$command .= "\$arguments[".$i."], ";
				}
				
				// Replace the last separator.
				//$command = eregi_replace(", $", ");", $command);
				$command = preg_replace("/, $/i", ");", $command);
				
				// Execute the command.
				eval($command);
			}
		
      // Display the error message.
      echo "<b>phpXML error:</b> ".$message;
        
      // End the execution of this script.
      exit;
    }

		//added by ddaemiri, 2007.05.28
		//entity �� �ϳ��� �ִٰ� ����!! �迭�� ù��°�� ������.
    function get_content_fetch ( $path )
		{
			$e = $this->evaluate($path);
			$content = $this->get_content($e[0]);
			$a = $this->get_attributes_patch( $path, "urlencode" );	
			if( $a != "" )
					$content = urldecode( $content );
			return $content;
		}
    function get_attributes_patch ( $path, $attr )
		{
			$e = $this->evaluate($path);
			$a = $this->get_attributes($e[0]);
			return $a[$attr];
		}

}

?>