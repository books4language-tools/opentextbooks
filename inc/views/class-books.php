<?php
/**
 * Project: opentextbooks
 * Project Sponsor: BCcampus <https://bccampus.ca>
 * Copyright 2012-2016 Brad Payne <https://bradpayne.ca>
 * Date: 2016-05-31
 * Licensed under GPLv3, or any later version
 *
 * @author Brad Payne
 * @package OPENTEXTBOOKS
 * @license https://www.gnu.org/licenses/gpl-3.0.txt
 * @copyright (c) 2012-2016, Brad Payne
 */

namespace BCcampus\OpenTextBooks\Views;

use BCcampus\OpenTextBooks\Config;
use BCcampus\OpenTextBooks\Models;
use BCcampus\Utility;

class Books {
	private $baseURL       = ''; // no value will generate relative urls
	private $authorBaseURL = 'http://solr.bccampus.ca:8001/bcc/access/searching.do?doc=';
	private $authorSearch1 = '%3Cxml%3E%3Ccontributordetails%3E%3Cname%3E';
	private $authorSearch2 = '%3C%2Fname%3E%3C%2Fcontributordetails%3E%3Clom%3E%3Clifecycle%3E%3Ccontribute%3E%3Ccentity%3E%3Cvcard%3E';
	private $authorSearch3 = '%3C%2Fvcard%3E%3C%2Fcentity%3E%3C%2Fcontribute%3E%3C%2Flifecycle%3E%3Cgeneral%3E%3Ckeyword%2F%3E%3C%2Fgeneral%3E%3C%2Flom%3E%3Citem%3E%3Crights%3E%3Coffer%3E%3Cparty%3E%3Ccontext%3E%3Cname%3E';
	private $authorSearch4 = '%3C%2Fname%3E%3C%2Fcontext%3E%3C%2Fparty%3E%3C%2Foffer%3E%3C%2Frights%3E%3Ckeywords%2F%3E%3Csubject_class_level1%2F%3E%3Csubject_class_level2%2F%3E%3Csubject_class_level1b%2F%3E%3Csubject_class_level2b%2F%3E';
	private $authorSearch5 = '%3C%2Fitem%3E%3COPDF%3E%3CBC_Course_Name%2F%3E%3COPDF_Tracking%2F%3E%3C%2FOPDF%3E%3C%2Fxml%3E&#38;in=Pae0d5e05-41bb-ccea-a5fd-f68a0ce34629&#38;q=&#38;sort=rank&#38;dr=AFTER';
	private $reviewed      = 'REVIEWED149df27a3ba8b2ddeff0d7ed1e6e54e4';
	private $ancillary     = 'ANCILLARY952a557ef465997b3acfb73fa4b609c7e61182b9';
	private $adopted       = 'AdoptedYesa37e464dc2330136a2c7f1138cf3c7a1';
	private $accessible    = 'AccessYes743d2920dc2c91040a3e48d6a6e32cc3';
	private $size;
	private $args;
	private $books;

	/**
	 * Books constructor.
	 *
	 * @param Models\OtbBooks $books
	 */
	public function __construct( Models\OtbBooks $books ) {
		if ( is_object( $books ) ) {
			$this->books = $books;
		}

		$this->size = count( $books->getResponses() );
		$this->args = $books->getArgs();

	}

	/**
	 *
	 */
	public function displayOneTextbook() {
		try {
			$env = Config::getInstance()->get();
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() );
		}
		$html_accordion_cards = '';
		$sources              = '';
		$data                 = $this->books->getResponses();

		$meta_xml         = simplexml_load_string( $data['metadata'] );
		$citation_pdf_url = $this->getCitationPdfUrl( $data['attachments'] );
		$cover            = preg_replace( '/^http:\/\//iU', '//', $meta_xml->item->cover );
		$created_date     = date( 'F j, Y', strtotime( $data['createdDate'] ) );
		$modified_date    = date( 'F j, Y', strtotime( $data['modifiedDate'] ) );
		$img              = ( $meta_xml->item->cover ) ? "<figure class='pt-3 m-0 text-center bg-light'><img itemprop='image' class='img-polaroid' src='{$cover}' alt='textbook cover image' width='151px' height='196px' /> <br>
														<small><a data-toggle='collapse' href='#collapseCopyright' role='button' aria-expanded='false' aria-controls='collapseExample'> Photo credit </a></small>
														<div class='collapse' id='collapseCopyright'> <div class='card card-body'>
														<figcaption><small class='text-muted copyright-notice'>" . $meta_xml->item->cover->attributes()->copyright . '</small></figcaption>
														 </div> </div></figure>' : '';
		$revision         = ( $meta_xml->item->daterevision && ! empty( $meta_xml->item->daterevision[0] ) ) ? '<h4 class="alert alert-info">Good news! An updated and revised version of this textbook will be available in ' . date( 'F j, Y', strtotime( $meta_xml->item->daterevision[0] ) ) . '</h4>' : '';
		$adaptation       = ( $meta_xml->item->adaptation->attributes()->value ) ? $meta_xml->item->adaptation->source : '';
		$authors          = Utility\array_to_csv( $data['drm']['options']['contentOwners'], 'name' );

		$html  = $this->getSimpleXmlMicrodata( $meta_xml, $citation_pdf_url );
		$html .= $this->getResultsMicrodata( $data );

		$html .= "<h2 itemprop='name'>" . $data['name'] . '</h2>';
		$html .= ( $created_date === $modified_date ) ? "<small><p class='text-muted'><b itemprop='datePublished'>Posted:</b> {$created_date} " : "<small><p class='text-muted'><b itemprop='datePublished'>Posted:</b> {$created_date} <b>&#124; Updated:</b> {$modified_date} ";
		$html .= "<br><strong>Author</strong>: <span itemprop='author copyrightHolder'>" . $authors . '</span></p></small>';
		$html .= '<p>' . $revision . '</p>';

		if ( ! empty( $adaptation ) ) {
			$html .= "<h4 class='alert alert-success'>Good news! This book has been updated and revised. An adaptation of this book can be found here: ";
			$html .= $this->formatUrl( $adaptation );
			$html .= '</h4>';
		}

		$html .= "<div class='row'><div class='col-sm-8'>";
		$html .= "<p><span itemprop='description'>" . $data['description'] . '</span></p>';
		$html .= "<p><strong>Subject Areas</strong><br><a href='?subject={$meta_xml->item->subject_class_level1}' itemprop='about'>{$meta_xml->item->subject_class_level1}</a>, <a href='?subject={$meta_xml->item->subject_class_level2}'>{$meta_xml->item->subject_class_level2}</a></p>";

		if ( is_object( $meta_xml->item->source ) && ! empty( $meta_xml->item->source ) ) {
			$html .= '<p><strong>Original source</strong><br>';

			foreach ( $meta_xml->item->source as $source ) {
				$sources .= $this->formatUrl( $source );
			}

			$sources = rtrim( $sources, ', ' );
			$html   .= $sources . '</p>';
		}

		$html .= $this->renderBookInfo();
		$html .= "</div><div class='col-sm-4'>";

		$grouped = $this->groupAttachmentsByType( $data['attachments'] );

		foreach ( $grouped as $type => $attachments ) {
			if ( empty( $attachments ) ) {
				continue;
			}

			$html_accordion_attachments = '';

			// get attachments
			foreach ( $attachments as $attachment ) {
				( array_key_exists( 'size', $attachment ) ) ? $file_size = Utility\determine_file_size( $attachment['size'] ) : $file_size = '';
				$logo_type                   = $this->addLogo( $attachment['description'] );
				$tracking                    = "_paq.push(['trackEvent','exportFiles','{$data['name']}','{$logo_type['type']}']);";
				$html_accordion_attachments .= sprintf(
					'<link itemprop="bookFormat" href="https://schema.org/EBook"><li class="p-1 mb-2" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
			              <meta itemprop="price" content="$0.00"><link itemprop="availability" href="https://schema.org/InStock">
			              <a class="btn btn btn-outline-primary btn-sm" role="button" onclick="%1$s" href="%2$s" title="%3$s">%4$s</a> <br> %5$s <br><span class="text-muted">%6$s</span></li>', $tracking, $attachment['links']['view'], $attachment['description'], $logo_type['string'], $attachment['description'], $file_size
				);
			}

			// wrap attachments in accordion card
			$html_accordion_cards .= sprintf(
				'<div class="card border-0">
				<div class="card-header border-0 p-1" id="headingOne">
				<h5 class="mb-0">
				<button class="btn btn-link" data-toggle="collapse" data-target="#collapse%1$s" aria-expanded="true" aria-controls="collapse%1$s">
			    %1$s <span class="badge badge-secondary">%2$s</span>
				</button>
				</h5>
				</div>
				<div id="collapse%1$s" class="collapse" aria-labelledby="heading%1$s" data-parent="#accordion">
				<div class="card-body bg-light p-1">
				<ul class="list-unstyled line-height-lg">%3$s</ul></div></div></div>', ucfirst( $type ), count( $attachments ), $html_accordion_attachments
			);

		}

		$html .= sprintf(
			'<div id="accordion">%1$s
			<div class="card-header text-center">
			<h4>Get This Book</h4>
			<span class="text-muted">Select a file format</span>
			%2$s</div></div>', $img, $html_accordion_cards
		);

		$html .= '</div></div>';

		//send it to the picker for evaluation
		$substring = $this->licensePicker( $data['metadata'], $authors );

		//include it, depending on what license it is
		$html .= $substring;
		echo $html;
	}

	/**
	 * @param $uuid
	 * @param $subject_areas
	 * @param $limit
	 *
	 * @return string
	 */
	public function displayRelevant( $uuid, $subject_areas, $limit ) {

		$sorted   = $this->books->sortByRelevance( $subject_areas, true );
		$env      = Config::getInstance()->get();
		$articles = '';
		$i        = 0;

		foreach ( $sorted as $book ) {
			if ( 0 === strcmp( $uuid, $book['uuid'] ) ) { // don't print the one we're on
				continue;
			}

			if ( $limit === $i ) {
				break;
			}

			$meta_xml = \simplexml_load_string( $book['metadata'] );
			$cover    = \preg_replace( '/^http:\/\//iU', '//', $meta_xml->item->cover );

			$articles .= sprintf(
				'
				<article class="col-md-3 mb-2 text-center" itemscope itemtype="http://schema.org/Article">
				<a href="%1$s">
				<img itemprop="image" class="img-polaroid" src="%2$s" alt="Image for the textbook titled %3$s" width="151px" height="196px" />
				</a>
				<p><a href="%1$s">%3$s</a></p>
				</article>', $env['domain']['scheme'] . $env['domain']['host'] . '/' . $env['domain']['app_path'] . '/?uuid=' . $book['uuid'], $cover, $book['name']
			);
			$i ++;
		}

		$html = sprintf(
			'
			<section class="bkgd-grey-light d-flex flex-row flex-wrap full-width py-3 mt-3">
			    <div class="col-12">
			        <h4>Similar Textbooks</h4>
			    </div>%1$s
			</section>', $articles
		);

		echo $html;
	}

	/**
	 * @return string
	 */
	private function renderBookInfo() {
		$expected = [ 'notifications', 'adoption', 'adaptation', 'help', 'accessibility', 'other' ];
		$html     = '';

		try {
			$env = Config::getInstance()->get();
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() );
		}

		foreach ( $expected as $entry ) {
			if ( isset( $env['domain'][ $entry ] ) && ! empty( $env['domain'][ $entry ] ) ) {
				$fa    = isset( $env['domain'][ $entry ]['fa'] ) ? $env['domain'][ $entry ]['fa'] : '';
				$font  = "<i class='fa fa-{$fa}'></i>";
				$html .= "<p><strong>{$env['domain'][$entry]['label']}</strong><br><a href='{$env['domain'][$entry]['path']}'>{$env['domain'][$entry]['text']} {$font}</a></p>";
			}
		}

		return $html;

	}

	/**
	 * Returns a simple form
	 *
	 * @param string $post_value
	 *
	 * @return string html blob with the postValue in it
	 */
	public function displaySearchForm( $post_value = '' ) {

		$html = '<section class="bkgd-blue-navy full-width py-4 px-5">
        <form class="mb-0" action="" method="get" role="search">
        <div class="form-group input-group">
			<label for="find-oer-1" class="sr-only">Search the BC Open Textbook Collection</label>
			<input type="text" class="form-control" placeholder="Search..." name="search" id="find-oer-1" aria-label="search terms" aria-describedby="find-oer-2"/>
			<div class="input-group-append">
				<select aria-label="search filters" class="form-control" id="filter" name="filter">
			    	<option value="">-- Filter --</option>
			    	<option value="accessible">Accessible</option>
			    	<option value="adopted">Adopted</option>
			    	<option value="ancillary">Ancillary</option>
			    	<option value="reviewed">Reviewed</option>
				</select>
			    <button type="submit" class="btn btn-primary" id="find-oer-2">Search</button>
			</div>
			<input type="hidden" name="contributor" value="' . $this->args['contributor'] . '"/>
			<input type="hidden" name="subject" value="' . urldecode( $this->args['subject'] ) . '"/>
			</div>
    	</form>
		</section>';

		echo $html;
	}


	/**
	 * Need to deliver the results in html. Depending on what variables are
	 * set this can display the records for one resource, a search form, an unordered, paginated list, or an ordered
	 * list of resources.
	 *
	 * @param int $start_here - the first record to start from (not zero based)
	 *
	 * @return String - an HTML blob of the results
	 */
	public function displayBooks( $start_here ) {
		$limit = 10;
		$html  = '';

		$start_here = intval( $start_here );
		if ( is_int( $start_here ) ) {
			//set the limit if there are less than 10 results based on where we start
			if ( ( $this->size - $start_here ) < 10 ) {
				//add a limit to the results, but avoid setting the limit to 0, since that'll give you more than you want
				$limit = ( $this->size - $start_here ) === 0 ? $limit = 1 : $this->size - $start_here;
			}

			$html .= $this->displaySearchForm( $this->args['search'] );

			//if the search term is empty, then set where it starts and limit it to ten
			if ( empty( $this->args['search'] ) ) {
				$html .= $this->displayBySubject( $start_here, $limit );
				$html .= $this->displayLinks( $start_here, $this->args['search'] );
			} else { //otherwise, display all the results starting at the first one (from a search form)
				$html .= $this->displayBySubject( 0, 0, $this->args );
			}
			echo $html;
		}
	}

	/**
	 *
	 * @param $limit
	 *
	 * @return string
	 */
	public function displayLatestAdditions( $limit ) {
		try {
			$env = Config::getInstance()->get();
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() );
		}

		$data = $this->books->sortByCreatedDate();
		$html = '';
		$i    = 0;

		foreach ( $data as $datum ) {
			$i ++;
			$meta_xml = simplexml_load_string( $datum['metadata'] );
			$cover    = preg_replace( '/^http:\/\//iU', '//', $meta_xml->item->cover );
			$html    .= ( $meta_xml->item->cover ) ? sprintf( '<a href="/%1$s/?uuid=%2$s"><img itemprop="image" class="img-polaroid" src="%3$s" alt="image of %4$s" width="151px" height="196px" /></a><p>%4$s</p>', $env['domain']['app_path'], $datum['uuid'], $cover, $datum['name'] ) : sprintf( '<p>%1$s</p>', $datum['name'] );
			if ( $i === $limit ) {
				break;
			}
		}

		echo $html;
	}

	/**
	 * @param $type
	 */
	public function displayTitlesByType( $type ) {
		$book_data = $this->books->getResponses();

		switch ( $type ) {
			case 'reviewed':
				$arg = $this->reviewed;
				break;
			case 'adopted':
				$arg = $this->adopted;
				break;
			case 'accessible':
				$arg = $this->accessible;
				break;
			case 'ancillary':
				$arg = $this->ancillary;
				break;
		}

		foreach ( $book_data as $data ) {
			if ( false !== mb_strpos( $data['metadata'], $arg ) ) {
				$name[]['name'] = $data['name'];
				$link[]['link'] = $data['uuid'];
			}
		}
		// sort alphabetically
		array_multisort( $name, $link );

		$count = count( $name );

		if ( 'ancillary' === $type ) {
			$html = "<p>There are currently {$count} textbooks with {$type} resources.</p>";
		} elseif ( 'accessible' === $type ) {
			$html = "<p>There are currently {$count} {$type} textbooks. Accessible textbooks must meet the criteria noted on the <a href='https://opentextbc.ca/accessibilitytoolkit/back-matter/appendix-checklist-for-accessibility-toolkit/'>Accessibility Checklist.</a></p>";
		} else {
			$html = "<p>There are currently {$count} {$type} textbooks.</p>";
		}

		$html .= '<ol>';

		foreach ( $name as $key => $value ) {
			$html .= "<li><a href='?uuid={$link[$key]['link']}'>{$value['name']}</a></li>";
		}

		$html .= '</ol>';

		echo $html;
	}

	/**
	 * Filters through an array by the keys you pass it, with a default limit of 10
	 * and unless specified otherwise, starting at the beginning of the array
	 *
	 * @param int $start
	 * @param int $limit
	 * @param array $args filter arbitrary metadata
	 *
	 * @return string
	 */
	public function displayBySubject( $start = 0, $limit = 0, $args = [] ) {
		$html             = '';
		$results          = '';
		$i                = 0;
		$expected_filters = [ 'reviewed', 'adopted', 'ancillary', 'accessible' ];
		$data             = $this->books->getResponses();

		//just in case a start value is passed that is greater than what is available
		if ( $start > $this->size ) {
			$html = "<p>That's it, no more records</p>";

			return $html;
		}

		// necessary to see the last record
		$start = ( $start === $this->size ? $start = $start - 1 : $start = $start );

		// if we're displaying all of the results (from a search form request)
		if ( $limit === 0 ) {
			$limit = $this->size;
			$html .= '<ol class="list-group">';
		} else {
			$html .= "<ul class='list-group'>";
		}

		while ( $i < $limit ) {
			$metadata = $this->getMetaData( $data[ $start ]['metadata'] );
			if ( isset( $args['filter'] ) && in_array( $args['filter'], $expected_filters, true ) ) { // check if it's been reviewed,adopted,ancillary,accessible
				if ( 0 === preg_match( "/{$args['filter']}/", $metadata ) ) {
					$this->size --;
					$start ++;
					$i ++;
					continue;
				}
			}
			$desc  = ( strlen( $data[ $start ]['description'] ) > 500 ) ? mb_substr( $data[ $start ]['description'], 0, 499 ) . '<a href=' . $this->baseURL . '?uuid=' . $data[ $start ]['uuid'] . '&contributor=' . $this->args['contributor'] . '&keyword=' . $this->args['keyword'] . '&subject=' . $this->args['subject'] . '>...[more]</a>' : $data[ $start ]['description'];
			$html .= '<li class="list-group-item">';
			$html .= "<h4><a href='" . $this->baseURL . '?uuid=' . $data[ $start ]['uuid'] . '&contributor=' . $this->args['contributor'] . '&keyword=' . $this->args['keyword'] . '&subject=' . $this->args['subject'] . "'>" . $data[ $start ]['name'] . '</a></h4>';
			$html .= '<p>Author(s): ' . Utility\array_to_csv( $data[ $start ]['drm']['options']['contentOwners'], 'name' ) . '</p>';
			$html .= '<p>Updated: ' . date( 'M j, Y', strtotime( $data[ $start ]['modifiedDate'] ) ) . '</p>';
			$html .= '<p><strong>Description:</strong> ' . $desc . '</p>';
			$html .= '<h4>' . $metadata . '</h4>';
			$html .= '</li>';
			$start ++;
			$i ++;
		}
		if ( $limit === $this->size ) {
			$html .= '</ol>';
		} else {
			$html .= '</ul>';
		}
		if ( $this->size > 0 ) {
			$results .= '<h5 class="bkgd-grey-light p-3 mb-0 clearfix"><span class="font-weight-light">Results:</span> ' . $this->size . ' Open Textbooks</h5>';
		} else {
			$results .= "<h5 class='bkgd-grey-light p-3 mb-0 clearfix'>Available: <span style='color:red;'>sorry, your search returned no results</span></h5>";
		}
		echo $results . $html;
	}

	/**
	 * for generating a list of titles used in contact forms
	 * on open.bccampus.ca
	 *
	 * @param array $num_reviews_per_book
	 */
	public function displayContactFormTitles( array $num_reviews_per_book ) {
		$html           = [];
		$do_not_display = [
			'a51191e6-45e4-4a57-af97-16f943b25d7e' => 'Open Modernisms Anthology Builder',
		];
		$titles         = '';
		if ( ! empty( $num_reviews_per_book ) ) {
			foreach ( $num_reviews_per_book as $uid => $book ) {
				if ( $book >= 4 ) {
					$omit[] = $uid;
				}
			}
		}

		foreach ( $this->books->getResponses() as $data ) {
			// omit if 4 or more reviews
			if ( in_array( substr( $data['uuid'], 0, 5 ), $omit, true ) || array_key_exists( $data['uuid'], $do_not_display ) ) {
				continue;
			} elseif ( false === Utility\has_canadian_edition( substr( $data['uuid'], 0, 5 ) ) ) {
				$html[] = ucfirst( $data['name'] );
			}
		}

		sort( $html, SORT_ASC | SORT_NATURAL );
		echo count( $html ) . '<br>';
		foreach ( $html as $title ) {
			$titles .= '"' . $title . '" ';
		}
		echo $titles;
	}

	/**
	 *
	 * @param int $start_here
	 * @param string $search_term
	 *
	 * @return string $html
	 */
	private function displayLinks( $start_here, $search_term ) {
		$limit  = 0;
		$by_ten = 0;

		//reduce startHere to a multiple of 10
		$start_here = ( 10 * intval( $start_here / 10 ) );

		//reduce limit to an integer value
		$limit = intval( $this->size / 10 );

		//if it is less than 10 or equal to 10, just return (all the links are on the page)
		if ( $limit === 0 || $this->size === 10 ) {
			return;
		}
		$html = '<section class="p-3"><p>';
		//otherwise, produce as many links as there are results divided by 10
		while ( $limit >= 0 ) {
			if ( $start_here === $by_ten ) {
				$html .= '<strong>' . $by_ten . '</strong> | ';
			} else {
				$html .= "<a href='?start=" . $by_ten . '&subject=' . $this->args['subject'] . '&contributor=' . $this->args['subject'] . '&searchTerm=' . $search_term . '&keyword=' . $this->args['keyword'] . "'>" . $by_ten . '</a> | ';
			}
			$by_ten = $by_ten + 10;
			$limit --;
		}
		$html .= ' <em>' . $this->size . ' available results</em></p></section>';

		//return html blob
		echo $html;
	}

	/**
	 * looks for the existence of specific xml nodes
	 * returns an html string
	 *
	 * @param string $metadata
	 *
	 * @return string $html
	 */
	private function getMetaData( $metadata ) {
		$html            = '';
		$reviewed_path   = '/item/reviewed';
		$adopt_path      = '/item/adopted';
		$accessible_path = '/item/accessibility';
		$ancillary_path  = '/item/ancillary';

		// sanity check
		if ( empty( $metadata ) ) {
			return '';
		}

		// in case response is not xml/invalid xml
		libxml_use_internal_errors( true );

		$obj = \simplexml_load_string( $metadata );
		$xml = \explode( "\n", $metadata );

		// catch errors, give them to php log
		if ( ! $obj ) {
			$errors = libxml_get_errors(); //@TODO do something with errors
			foreach ( $errors as $error ) {
				$msg = $this->displayXmlError( $error, $xml );
				\error_log( $msg, 0 );
			}
			\libxml_clear_errors();
		}

		if ( is_object( $obj ) ) {
			// check for existence of nodes
			if ( false !== $obj->xpath( $reviewed_path ) ) {
				$html .= ( 0 === strcmp( $this->reviewed, $obj->item->reviewed ) ) ? " <small><a class='badge badge-success' href='?lists=reviewed'>Faculty reviewed</a></small> " : '';
			}

			if ( false !== $obj->xpath( $adopt_path ) ) {
				$html .= ( 0 === strcmp( $this->adopted, $obj->item->adopted ) ) ? " <small><a class='badge badge-success' href='?lists=adopted'>Adopted</a></small> " : '';
			}

			if ( false !== $obj->xpath( $accessible_path ) ) {
				$html .= ( 0 === strcmp( $this->accessible, $obj->item->accessibility ) ) ? " <small><a class='badge badge-success' href='?lists=accessible'>Accessible</a></small> " : '';
			}

			if ( false !== $obj->xpath( $ancillary_path ) ) {
				$html .= ( 0 === strcmp( $this->ancillary, $obj->item->ancillary ) ) ? " <small><a class='badge badge-success' href='?lists=ancillary'>Ancillary Resources</a></small> " : '';
			}
		}

		return $html;
	}

	/**
	 *
	 * @param string $source
	 *
	 * @return string $formatted url
	 */
	protected function formatUrl( $source ) {
		$formatted = '';
		try {
			$env = Config::getInstance()->get();
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() );
		}

		// check if it's a url
		if ( ! filter_var( $source, FILTER_VALIDATE_URL ) ) {
			$formatted .= "<span itemprop='isBasedOnUrl'>" . $source . '</span>, ';
		} else {
			$url = parse_url( $source );
			// change the base domain if we're not in the base domain environment
			if ( 0 === strcmp( $url['host'], 'open.bccampus.ca' ) && 0 !== strcmp( $env['domain']['host'], 'open.bccampus.ca' ) ) {
				$url['host'] = $env['domain']['host'];
			}
			if ( is_array( $url ) ) {
				$scheme = ( isset( $url['scheme'] ) ) ? $url['scheme'] . '://' : '';
				$host   = ( isset( $url['host'] ) ) ? $url['host'] : '';
				$path   = ( isset( $url['path'] ) ) ? $url['path'] : '';
				$query  = ( isset( $url['query'] ) ) ? '?' . $url['query'] : '';

				$based_on = $scheme . $host . $path . $query;

			}
			$formatted .= "<a itemprop='isBasedOnUrl' href='" . $based_on . "'>" . $url['host'] . ' </a>';

		}

		return $formatted;
	}

	/**
	 * @param array $attachments
	 *
	 * @return string
	 */
	private function getCitationPdfUrl( array $attachments ) {
		$redirect_url = '';
		try {
			$env = Config::getInstance()->get();
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() );
		}
		$base = "{$env['domain']['scheme']}{$env['domain']['host']}/wp-content/opensolr/opentextbooks/redirects.php";

		foreach ( $attachments as $attachment ) {
			if ( 'file' === $attachment['type'] && isset( $attachment['filename'] ) ) {
				$filetype = strstr( $attachment['filename'], '.' );
				if ( '.pdf' === $filetype && ! empty( $attachment['links']['view'] ) ) {
					$link       = $attachment['links']['view'];
					$parts      = parse_url( $link );
					$uuid_parts = explode( '/', $parts['path'] );

					// expecting /bcc/items/70fa0825-d41b-4519-975b-71bc2ea1f704/1/
					$uuid = $uuid_parts[3];

					// expecting attachment.uuid=70fa0825-d41b-4519-975b-71bc2ea1f704
					if ( isset( $parts['query'] ) ) {
						$a_uuid = ltrim( strstr( $parts['query'], '=' ), '=' );
					}

					$redirect_url = $base . '?uuid=' . $uuid . '&attachment.uuid=' . $a_uuid;
				}
			}
		}

		return $redirect_url;
	}

	/**
	 * Returns array of attachments of the requested type
	 *
	 * @param array $attachments
	 *
	 * @return array
	 */
	private function groupAttachmentsByType( array $attachments ) {
		$files['ancillary'] = [];
		$files['readable']  = [];
		$files['editable']  = [];
		$files['print']     = [];
		$readable           = [ '.pdf', '.epub', '.mobi', '.hpub', '.url' ];
		$editable           = [
			'.xml',
			'.html',
			'.odt',
			'.docx',
			'.doc',
			'._vanilla.xml',
			'.rtf',
			'.tex',
			'.zip',
			'.editable',
			'.gh',
		];
		$ancillary          = [ '.ancillary' ];
		$print              = [ '.print' ];

		foreach ( $attachments as $key => $attachment ) {
			$file_type = '';

			// deal with url attachments
			if ( isset( $attachment['url'] ) ) {
				$url = parse_url( $attachment['url'] );
				// give it a print filetype if it's coming from sfu domain, or has the string "print copy"
				if ( isset( $url['host'] ) && 0 === strcmp( 'opentextbook.docsol.sfu.ca', $url['host'] ) || 1 === preg_match( '/print(\s*)copy/iU', $attachment['description'] ) ) {
					$file_type = '.print';
				} elseif ( ( isset( $attachment['description'] ) ) && ( 1 === preg_match( '/^editable/iU', $attachment['description'] ) ) ) { // give it an editable file type if it has the string "editable"
					$file_type = '.editable';
				} elseif ( ( isset( $attachment['description'] ) ) && ( 1 === preg_match( '/^(ancillary|student|instructor)(\s*)resource(s?)/iU', $attachment['description'] ) ) ) {
					$file_type = '.ancillary';
					// if its a github url, give it a .gh value, which is in the editable array
				} elseif ( isset( $url['host'] ) && 0 === strcmp( 'github.com', $url['host'] ) ) {
					$file_type = '.gh';
				} else { // otherwise it's just a regular url
					$file_type = '.url';
				}
			}

			// check if it's in ancillary resource
			if ( isset( $attachment['description'] ) && ( $file_type !== '.ancillary' ) ) {
				if ( ( 1 === preg_match( '/^(ancillary|student|instructor)(\s*)resource(s?)/iU', $attachment['description'] ) ) ) {
					$file_type = '.ancillary';
				}
			}

			// If file type was not set by any of the above, let's grab it from the file name
			if ( empty( $file_type ) ) {
				if ( isset( $attachment['filename'] ) ) {
					$file_type = strrchr( $attachment['filename'], '.' );
				}
			}

			// build the requested file type array
			( in_array( $file_type, $readable, true ) ) ? array_push( $files['readable'], $attachments[ $key ] ) : '';
			( in_array( $file_type, $editable, true ) ) ? array_push( $files['editable'], $attachments[ $key ] ) : '';
			( in_array( $file_type, $ancillary, true ) ) ? array_push( $files['ancillary'], $attachments[ $key ] ) : '';
			( in_array( $file_type, $print, true ) ) ? array_push( $files['print'], $attachments[ $key ] ) : '';

		}

		return $files;
	}

	/**
	 * Hits the creative commons api, gets an xml response.
	 *
	 * @param string $string
	 * @param string $authors
	 *
	 * @return string $html license blob
	 */
	private function licensePicker( $string, $authors ) {
		$v3       = false;
		$endpoint = 'https://api.creativecommons.org/rest/1.5/';
		$expected = [
			'cc0'         => [
				'license'     => 'zero',
				'commercial'  => 'y',
				'derivatives' => 'y',
			],
			'cc-by'       => [
				'license'     => 'standard',
				'commercial'  => 'y',
				'derivatives' => 'y',
			],
			'cc-by-sa'    => [
				'license'     => 'standard',
				'commercial'  => 'y',
				'derivatives' => 'sa',
			],
			'cc-by-nd'    => [
				'license'     => 'standard',
				'commercial'  => 'y',
				'derivatives' => 'n',
			],
			'cc-by-nc'    => [
				'license'     => 'standard',
				'commercial'  => 'n',
				'derivatives' => 'y',
			],
			'cc-by-nc-sa' => [
				'license'     => 'standard',
				'commercial'  => 'n',
				'derivatives' => 'sa',
			],
			'cc-by-nc-nd' => [
				'license'     => 'standard',
				'commercial'  => 'n',
				'derivatives' => 'n',
			],
		];
		// interpret string as an object
		$xml = \simplexml_load_string( $string );

		if ( $xml ) {

			$license = $xml->lom->rights->description[0];
			$license = strtolower( $license );
			$title   = $xml->lom->general->title[0];
			$lang    = mb_substr( $xml->lom->general->language[0], 0, 2 );
		}

		// nothing meaningful to hit the api with, so bail
		if ( ! array_key_exists( $license, $expected ) ) {
			// try this first
			try {
				$license = $this->v3license( $license );
				$v3      = true;
			} catch ( \Exception $exc ) {
				\error_log( $exc->getMessage() );

				// get out of here
				return $license;
			}
		}

		$key = array_keys( $expected[ $license ] );
		$val = array_values( $expected[ $license ] );

		// build the url
		$url = $endpoint . $key[0] . '/' . $val[0] . '/get?' . $key[1] . '=' . $val[1] . '&' . $key[2] . '=' . $val[2] . '&creator=' . rawurlencode( $authors ) . '&title=' . rawurlencode( $title ) . '&locale=' . $lang;

		// go and get it
		$c = curl_init( $url );
		curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $c, CURLOPT_TIMEOUT, 20 );

		$response = curl_exec( $c );
		curl_close( $c );

		// if server response is not ok, return semi-meaningful string
		if ( false === $response ) {
			return 'license information currently unavailable from https://api.creativecommons.org/rest/1.5/';
		}

		// in case response is not xml/invalid xml
		libxml_use_internal_errors( true );

		$obj = simplexml_load_string( $response );
		$xml = explode( "\n", $response );

		// catch errors, give them to php log
		if ( ! $obj ) {
			$errors = libxml_get_errors(); //@TODO do something with errors
			foreach ( $errors as $error ) {
				$msg = $this->displayXmlError( $error, $xml );
				\error_log( $msg, 0 );
			}
			libxml_clear_errors();
		}

		// ensure instance of SimpleXMLElement, to avoid fatal error
		if ( is_object( $obj ) ) {
			$result = $this->getWebLicenseHtml( $obj->html );
		} else {
			$result = '';
		}

		// modify it for v3 if need be
		if ( true === $v3 ) {
			$result = preg_replace( '/(4\.0)/', '3.0', $result );
		}

		return $result;
	}

	/**
	 * @param \SimpleXMLElement $metaxml
	 * @param string $citation_pdf_url
	 *
	 * @return string
	 */
	private function getSimpleXmlMicrodata( \SimpleXMLElement $metaxml, $citation_pdf_url ) {
		$html = '';
		// meta elements not represented in the content
		$html .= "<meta itemprop='publisher' content='BCcampus'>\n";
		$html .= "<meta itemprop='educationalUse' content='Open textbook study'>\n";
		$html .= "<meta itemprop='audience' content='student'>\n";
		$html .= "<meta itemprop='interactivityType' content='mixed'>\n";
		$html .= "<meta itemprop='learningResourceType' content='textbook'>\n";
		$html .= "<meta itemprop='typicalAgeRange' content='17+'>\n";
		$html .= $this->getEducationalAlignment( $metaxml );
		$html .= "<meta itemprop='inLanguage' content='{$metaxml->lom->general->language}'>\n";
		$html .= "<meta name='citation_title' content='{$metaxml->lom->general->title}'>\n";
		$html .= "<meta name='citation_language' content='{$metaxml->lom->general->language}'>\n";
		$html .= "<meta name='citation_keywords' content='{$metaxml->item->subject_class_level1}'>\n";
		$html .= "<meta name='citation_keywords' content='{$metaxml->item->subject_class_level2}'>\n";
		$html .= "<meta name='citation_pdf_url' content='{$citation_pdf_url}'>\n";

		return $html;
	}

	/**
	 * @param array $results
	 *
	 * @return string|void
	 */
	private function getResultsMicrodata( array $results ) {
		$html = '';
		if ( ! is_array( $results ) ) {
			return;
		}

		foreach ( $results['drm']['options']['contentOwners'] as $owner ) {
			$author = ( false !== strstr( $owner['name'], ',', true ) ) ? strstr( $owner['name'], ',', true ) : $owner['name'];
			$html  .= "<meta name='citation_author' content='{$author}'>\n";
		}
		$date  = date( 'Y/m/d', strtotime( $results['createdDate'] ) );
		$html .= "<meta name='citation_online_date' content='{$date}'>\n";
		$html .= "<meta name='citation_publication_date' content='{$date}'>\n";
		$html .= "<meta itemprop='datePublished' content='{$results['createdDate']}'>\n";
		$html .= "<meta itemprop='dateModified' content='{$results['modifiedDate']}'>\n";
		$html .= "<meta itemprop='url' content='{$results['links']['view']}'>\n";

		return $html;
	}

	/**
	 * @param \SimpleXMLElement $metaxml
	 *
	 * @return string
	 */
	private function getEducationalAlignment( \SimpleXMLElement $metaxml ) {
		$csv = '';

		if ( isset( $metaxml->item->subject_class_level1 ) ) {
			$csv .= $metaxml->item->subject_class_level1;
		}
		if ( isset( $metaxml->item->subject_class_level2 ) ) {
			$csv .= ', ' . $metaxml->item->subject_class_level2;
		}
		if ( isset( $metaxml->item->subject_class_level3 ) ) {
			$csv .= ', ' . $metaxml->item->subject_class_level3;
		}

		$html = "<meta itemprop='educationalAlignment' content='" . $csv . "'>\n";

		return $html;
	}

	/**
	 * helper function to evaluate the type of document and add the appropriate logo
	 *
	 * @param $string
	 *
	 * @return array
	 */
	private function addLogo( $string ) {

		if ( ! stristr( $string, 'print copy' ) === false ) {
			$result = [
				'string' => "PRINT <i class='fa fa-print'></i>",
				'type'   => 'print',
			];
		} else {
			$result = [
				'string' => "<i class='fa fa-globe'></i> WEBSITE <img src='" . OTB_URL . "assets/images/document-code.png' alt='External website. This icon is licensed under a Creative Commons
		Attribution 3.0 License. Copyright Yusuke Kamiyamane. '/>",
				'type'   => 'url',
			];
		}

		//if it's a zip
		if ( ! stristr( $string, '.zip' ) === false || ! stristr( $string, '.tbz' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . OTB_URL . "assets/images/document-zipper.png' alt='ZIP file '/>",
				'type'   => 'zip',
			];
		}
		//if it's a word file
		if ( ! stristr( $string, 'Word' ) === false || ! stristr( $string, '.rtf' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . OTB_URL . "assets/images/document-word.png' alt='WORD file'/>",
				'type'   => 'doc',
			];
		}
		//if it's a pdf
		if ( ! stristr( $string, 'PDF' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . OTB_URL . "assets/images/document-pdf.png' alt='PDF file'/>",
				'type'   => 'pdf',
			];
		}
		//if it's an epub
		if ( ! stristr( $string, 'eReader' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . OTB_URL . "assets/images/document-epub.png' alt='EPUB file'/>",
				'type'   => 'epub',
			];
		}
		//if it's a mobi
		if ( ! stristr( $string, 'Kindle' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . OTB_URL . "assets/images/document-mobi.png' alt='MOBI file'/>",
				'type'   => 'mobi',
			];
		}
		// if it's a wxr
		if ( ! stristr( $string, 'XML' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . OTB_URL . "assets/images/document-xml.png' alt='XML file' />",
				'type'   => 'xml',
			];
		}
		// if it's an odt
		if ( ! stristr( $string, 'OpenDocument Text' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . OTB_URL . "assets/images/document.png' alt='ODT file' />",
				'type'   => 'odt',
			];
		}
		if ( ! stristr( $string, '.hpub' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . OTB_URL . "assets/images/document.png' alt='HPUB file' />",
				'type'   => 'hpub',
			];
		}
		if ( ! stristr( $string, 'HTML' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . OTB_URL . "assets/images/document-code.png' alt='XHTML file' />",
				'type'   => 'html',
			];
		}
		// if it's a tex
		if ( ! stristr( $string, '.tex' ) === false ) {
			$result = [
				'string' => "<i class='fa fa-download'></i> <span class='small-for-mobile'>DOWNLOAD</span> <img src='" . OTB_URL . "assets/images/document-tex.png' alt='TEX file' />",
				'type'   => 'tex',
			];
		}

		return $result;
	}


	/**
	 * @param $error
	 * @param $xml
	 *
	 * @return string
	 */
	protected function displayXmlError( $error, $xml ) {
		$return  = $xml[ $error->line - 1 ];
		$return .= str_repeat( '-', $error->column );

		switch ( $error->level ) {
			case LIBXML_ERR_WARNING:
				$return .= "Warning $error->code: ";
				break;
			case LIBXML_ERR_ERROR:
				$return .= "Error $error->code: ";
				break;
			case LIBXML_ERR_FATAL:
				$return .= "Fatal Error $error->code: ";
				break;
		}

		$return .= trim( $error->message ) . "  Line: $error->line" . "  Column: $error->column";

		if ( $error->file ) {
			$return .= "  File: $error->file";
		}

		return "$return--------------------------------------------END";
	}

	/**
	 * Checks for part of a string, removes it and returns to make it something
	 * the API can deal with
	 *
	 * @param type $license
	 *
	 * @return type
	 * @throws \Exception
	 */
	protected function v3license( $license ) {

		// check for string match CC-BY-3.0
		if ( false === mb_strpos( $license, '-3.0' ) ) {
			throw new \Exception( ' no valid license passed at Filter\v3license' );
		}

		$license = strtolower( strstr( $license, '-3.0', true ) );

		return $license;
	}

	/**
	 * Helper function customizes html response from cc api
	 *
	 * @param \SimpleXMLElement $response
	 *
	 * @return type
	 */
	private function getWebLicenseHtml( \SimpleXMLElement $response ) {
		$html = '';

		if ( is_object( $response ) ) {
			$content = $response->asXML();
			$content = trim(
				str_replace(
					[
						'<p xmlns:dct="http://purl.org/dc/terms/">',
						'</p>',
						'<html>',
						'</html>',
					], [ '', '', '', '' ], $content
				)
			);
			$content = preg_replace( '/http:\/\/i.creativecommons/iU', 'https://i.creativecommons', $content );

			$html = '<div class="license-attribution" xmlns:cc="http://creativecommons.org/ns#"><small><p class="text-muted" xmlns:dct="http://purl.org/dc/terms/">'
					. rtrim( $content, '.' ) . ', except where otherwise noted.</p></small></div>';
		}

		return html_entity_decode( $html, ENT_XHTML, 'UTF-8' );
	}

	/**
	 * Helper function to generate a short url for the resource
	 *
	 * @param type $url
	 *
	 * @return string
	 */
	private function displayShortURL( $url ) {
		$url_encode = rawurlencode( $url );
		try {
			$env = Config::getInstance()->get();
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() );
		}
		$urls = $env['yourls']['url'] . '?signature=' . $env['yourls']['uuid'] . '&action=shorturl&format=simple&url=';

		// get the string result
		$result  = '<p><strong>Short URL</strong>: ';
		$result .= "<input type='text' name='yourl' id='yourl' value='";
		$result .= file_get_contents( $urls . $url_encode );
		$result .= "' size='30''></p>";

		return $result;
	}

	/**
	 * helper function to add the ominus author links to the authors printed
	 * for individual records.
	 *
	 * @param type $authors
	 *
	 * @return type
	 */
	private function addAuthorLinks( $authors ) {
		$result    = '';
		$tmp_array = '';
		//if the string passed has only one value, or no commas
		if ( ! strstr( $authors, ',' ) ) {
			$result = "<a title='more from this author' href='" . $this->authorBaseURL . $this->authorSearch1 . $authors . $this->authorSearch2 . $authors . $this->authorSearch3 . $authors . $this->authorSearch4 . $this->authorSearch5 . "'>" . $authors . '</a>';
		} else { //otherwise, if there is more than one author
			$result = explode( ',', $authors );
			for ( $i = 0; $i < count( $result ); $i ++ ) {
				$tmp_array[ $i ] = "<a title='more from this author' href='" . $this->authorBaseURL . $this->authorSearch1 . $result[ $i ] . $this->authorSearch2 . $result[ $i ] . $this->authorSearch3 . $result[ $i ] . $this->authorSearch4 . $this->authorSearch5 . "'>" . $result[ $i ] . '</a>';
			}
			$result = Utility\array_to_csv( $tmp_array );
		}

		return $result;
	}


}
