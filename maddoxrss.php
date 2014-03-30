<?php
	function file_put_contents( $filename, $content){
		$h = fopen($filename, "w+");
		fwrite( $h, $content);
		fclose( $h );

	}


	require_once("feedcreator.class.php");
	header('Content-type: text/xml');

	$data = file_get_contents( "http://www.thebestpageintheuniverse.net/"  );
	$pattern = '/\n<A HREF="(.*)">(.*)<\/A>\n<font size=3><br>\n\(Updated: (\d\d-\d\d-\d\d)\)\n<\/font>\n<BR>\n<P>/miU';
	preg_match_all( $pattern, $data, $matches );

	//1 is the URL
	//2 is the title
	//3 is the date
	
	//Check for first article to see if complete cache is available
	$newest = $matches[3][0];
	$contents = file_get_contents( "maddoxcache/feed." . $newest . ".cache" );
	if(!$contents){	
		$rss = new UniversalFeedCreator(); 
		//$rss->useCached(); 
		$rss->title = "The Best Page in the Universe"; 
		$rss->description = "Maddox"; 
		$rss->link = "http://www.thebestpageintheuniverse.net/"; 
		$rss->syndicationURL = "http://www.thebestpageintheuniverse.net/" . $PHP_SELF; 

		$image = new FeedImage(); 
		$image->title = "Maddox"; 
		$image->url = "http://talkshowhost.net/images/maddox.gif"; 
		$image->link = "http://www.thebestpageintheuniverse.net/"; 
		$image->description = "Maddox"; 
		$rss->image = $image;


		for($i = 0; $i < SizeOf( $matches[1] ); $i++){
			$url = $matches[1][$i];

			//Add in domain if it's missing
			//looks like maddox has some stuff on maddox.xmission.net and other stuff on bestbpage.
			//If the domains not there, then it's on maddox.xmission
			if( substr($url, 0, 4) != "http"){
				$url = "http://maddox.xmission.net/" . $url;
			}

			$equalcharat = strrpos($url, "=");

			//Only do articles with = signs as these are the new format
			if(!$equalcharat) continue;

			//Format the date for RSS YYYY-MM-DDTHH:MM:SS
			$date = explode("-", $matches[3][$i]);
			$date = "20" . $date[2] . "-" . $date[0] . "-" . $date[1];

			$item = new FeedItem();
			$item->title = $matches[2][$i];
			$item->link = $url;

			//Check if the article is cached locally
			//Need to create a valid file name for the cache from the url supplied + update date

			$cachefile = substr( $url, $equalcharat+1) . "." . $date . ".cache";
			$content = file_get_contents( "maddoxcache/" . $cachefile );
			
			/*
			print( "File: $cachefile<br>" );
			print( "File: $url<br>" );
			print("<textarea>" . $content . "</textarea><br>");
			*/

			if( $content == false ){
				//print("$cachefile not found --> From URL<br/>");
				$content = file_get_contents( $url );

				file_put_contents( "maddoxcache/" . $cachefile, $content );
			}

			$item->description = $content;

			$item->date = $date . "T00:00:00";
			$item->source = $url;
			$item->author = "Maddox";
			 
			$rss->addItem($item);

		}

		$rss->_setFormat("RSS2.0");
		file_put_contents( "maddoxcache/feed." . $newest . ".cache", $rss->_feed->createFeed() );
	}

	print( file_get_contents( "maddoxcache/feed." . $newest . ".cache" ) );

?>