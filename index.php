<?php
ini_set('display_errors', '1');
ini_set('error_reporting', E_ALL & ~E_NOTICE);
ini_set('memory_limit','1G');
set_time_limit(0);
ini_set('max_execution_time', 0); //300 seconds = 5 minutes

libxml_use_internal_errors(true);

//the name of the curl function
function curl_get_contents($url){

    //Initiate the curl
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    //removes the header of the webpage
    curl_setopt($ch, CURLOPT_HEADER, 0);
    //do not display the whole page
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //execute the curl
    $output = curl_exec($ch);
    //close the curl so that resources are not wasted
    //curl_close($ch);
    return $output;
}

//membuat csv file
function createCSV($data, $category){
    $curlinit = curl_init();

    $listNow = "file/".date("Y.m.d")."_".$category.'_'.time();
    $file = fopen($listNow.".csv","w");
    foreach ($data as $line){
        fputcsv($file, $line);
    }
    fclose($file); 
    echo "File CSV Saved.. (check folder '/file' on your localhost directory)";
}

class ImageFactory{
    public  function MakeThumb($thumb_target = '', $width = 150,$height = 150,$SetFileName = false, $quality = 80){
        $thumb_img  =   imagecreatefromjpeg($thumb_target);

        // size from
        list($w, $h) = getimagesize($thumb_target);

        if($w > $h) {
            $new_height =   $height;
            $new_width  =   floor($w * ($new_height / $h));
            $crop_x     =   ceil(($w - $h) / 2);
            $crop_y     =   0;
        }else {
            $new_width  =   $width;
            $new_height =   floor( $h * ( $new_width / $w ));
            $crop_x     =   0;
            $crop_y     =   ceil(($h - $w) / 2);
        }

        // I think this is where you are mainly going wrong
        $tmp_img = imagecreatetruecolor($width,$height);

        imagecopyresampled($tmp_img, $thumb_img, 0, 0, $crop_x, $crop_y, $new_width, $new_height, $w, $h);

        if($SetFileName == false) {
                header('Content-Type: image/jpeg');
                imagejpeg($tmp_img);
            }
        else
            imagejpeg($tmp_img,$SetFileName,$quality);

        imagedestroy($tmp_img);
    }
}
?>
<form class="col s12" method="POST" action="#">
	<div>
		<h4>Grab photo from istockphoto.com</h4>
	</div>
	<div>
		<input placeholder="Kategory Gambar" type="text" class="validate" name="category" required>
	</div>
	<div>
		<input type="number" class="validate" name="totalImages">
	</div>
	<div>
		<button type="submit" name="submit" style="margin-top:10px">Proses..!!!</button>
	</div>
</form>
<?php
if(isset($_POST['submit'])){
	$category 		= $_POST['category'];
	$totalImages 	= intval($_POST['totalImages']);
	$totalPage		= floor($totalImages/200); //200 adalah item terbanyak yang dapat ditampilkan dalam 1 halaman

	$data = array();
	$data[0] = ["Nama File"];
	$x = 1;

	for ($page=1; $page <=$totalPage ; $page++) {
		$url = "http://www.istockphoto.com/photos/".$category."?facets=%7B%22text%22:%5B%22".$category."%22%5D,%22pageNumber%22:".$page.",%22perPage%22:".$totalImages.",%22abstractType%22:%5B%22photos%22,%22illustrations%22%5D,%22order%22:%22bestMatch%22,%22f%22:true%7D";

		$date = date("j-M-Y h:i:s");
	    echo '['.$date.'] Parsing : '.$url."</br>";
	    $output = curl_get_contents($url);
	    if($output) {
	        // Get the complete html from  the address
	        $dom = new DOMDocument;
	        $dom->loadHTML($output);

	        $imgNum = 1;
	        foreach($item = $dom->getElementsByTagName('img') as $imgtags){
	        	$img = $imgtags->getAttribute('src');

	        	$File = "images/".$category."_".$page.'_'.$imgNum.".jpg";
				$ch1 = curl_init($img);
				$fp1 = fopen($File, 'wb');
				curl_setopt($ch1, CURLOPT_FILE, $fp1);
				curl_setopt($ch1, CURLOPT_HEADER, 0);
				curl_exec($ch1);
				curl_close($ch1);
				fclose($fp1);
				echo 'Page : '.$page.'. : Image '.$imgNum.' Downloaded..';
				echo "</br>";

				// Initiate class
				$ImageMaker 	= new ImageFactory();
				$thumb_target   = $File;
				$squareImg		= "images/resize/".$category."_170x170_".$page.'_'.$imgNum.".jpg";
				$ImageMaker->MakeThumb($thumb_target,170,170,$squareImg);

				$imgName = $category."_170x170_".$page.'_'.$imgNum.".jpg";
				$data[$x][0] = $imgName;

				$imgNum++;
				$x++;
	        }
	    }
	}	
createCSV($data, $category);
}
?>