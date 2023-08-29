<?php
/**
 * Plugin Name: WP-RSSImport
 * Plugin URI:  https://de.wordpress.org/plugins/rss-import/
 * Text Domain: rssimport
 * Domain Path: /languages
 * Description: Display Feeds in your blog. Use the function <code>RSSImport()</code>, a Widget or the Shortcode <code>[RSSImport]</code>.
 * Author:      Frank Bültge, took77
 * Version:     4.6.2
 * License:     GPLv3+
 * Last change: 2022-12-23
 */

/**
------------------------------------------------------------
 ACKNOWLEDGEMENTS
------------------------------------------------------------
Original and Idea: Dave Wolf, http://www.davewolf.net
Thx to Thomas Fischer, http://www.securityfocus.de and
Gunnar Tillmann http://www.gunnart.de for a rearrange code,
Ilya Shindyapin, http://skookum.com for adding of paging
*/

//avoid direct calls to this file, because now WP core and framework has been used
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( function_exists( 'add_action' ) ) {
	//WordPress definitions
	if ( ! defined( 'WP_CONTENT_URL' ) ) {
		define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
	}
	if ( ! defined( 'WP_CONTENT_DIR' ) ) {
		define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
	}
	if ( ! defined( 'WP_PLUGIN_URL' ) ) {
		define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins' );
	}
	if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
		define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
	}
	if ( ! defined( 'PLUGINDIR' ) ) {
		define( 'PLUGINDIR', 'wp-content/plugins' );
	} // Relative to ABSPATH. For back combat
	if ( ! defined( 'WP_LANG_DIR' ) ) {
		define( 'WP_LANG_DIR', WP_CONTENT_DIR . '/languages' );
	}

	// plugin definitions
	define( 'FB_RSSI_BASENAME', plugin_basename( __FILE__ ) );
	define( 'FB_RSSI_BASEFOLDER', plugin_basename( __DIR__ ) );
	define( 'FB_RSSI_TEXTDOMAIN', 'rssimport' );
	define( 'FB_RSSI_QUICKTAG', true );
}

function RSSImport_textdomain() {
	if ( function_exists( 'load_plugin_textdomain' ) ) {
		load_plugin_textdomain( FB_RSSI_TEXTDOMAIN, false, dirname( FB_RSSI_BASENAME ) . '/languages' );
	}
}

// Cache reporting.
// define('MAGPIE_CACHE_ON', FALSE); // Cache off
if ( ! defined( 'MAGPIE_CACHE_AGE' ) ) {
	define( 'MAGPIE_CACHE_AGE', '60*60' ); // in sec, one hour
}

/**
 * @param int $display
 * @param string $feedurl
 * @param string $before_desc
 * @param int $displaydescriptions
 * @param string $after_desc
 * @param int $html
 * @param int $truncatedescchar
 * @param string $truncatedescstring
 * @param int $truncatetitlechar
 * @param string $truncatetitlestring
 * @param string $before_date
 * @param int $date
 * @param string $after_date
 * @param string $date_format
 * @param string $before_creator
 * @param int|string $creator
 * @param string $after_creator
 * @param string $start_items
 * @param string $end_items
 * @param string $start_item
 * @param string $end_item
 * @param string $target
 * @param string $rel
 * @param int $desc4title
 * @param int $charsetscan
 * @param int $debug
 * @param string $before_noitems
 * @param string $noitems
 * @param string $after_noitems
 * @param string $before_error
 * @param string $error
 * @param string $after_error
 * @param int $paging
 * @param string $prev_paging_link
 * @param string $next_paging_link
 * @param string $prev_paging_title
 * @param string $next_paging_title
 * @param int $use_simplepie
 * @param int $view
 * @param int $random_sort
 * @param string $order
 *
 * @return string
 */
function RSSImport(
	$display = 5,
	$feedurl = 'https://bueltge.de/feed/',
	$before_desc = '',
	$displaydescriptions = 0,
	$after_desc = '',
	$html = 0,
	$truncatedescchar = 200,
	$truncatedescstring = ' ... ',
	$truncatetitlechar = 0,
	$truncatetitlestring = ' ... ',
	$before_date = ' <small>',
	$date = 0,
	$after_date = '</small>',
	$date_format = '',
	$before_creator = ' <small>',
	$creator = 0,
	$after_creator = '</small>',
	$start_items = '<ul>',
	$end_items = '</ul>',
	$start_item = '<li>',
	$end_item = '</li>',
	$target = '',
	$rel = '',
	$desc4title = 0,
	$charsetscan = 0,
	$debug = 0,
	$before_noitems = '<p>',
	$noitems = 'No items, feed is empty.',
	$after_noitems = '</p>',
	$before_error = '<p>',
	$error = 'Error: Feed has an error or is not valid.',
	$after_error = '</p>',
	$paging = 0,
	$prev_paging_link = '&laquo; Previous',
	$next_paging_link = 'Next &raquo;',
	$prev_paging_title = 'more items',
	$next_paging_title = 'more items',
	$use_simplepie = 1,
	$view = 1,
	$random_sort = 0,
	$order = 'date,title,creator,description'
) {
	// replace for yahoo pipes urls
	$feedurl = str_replace( '&#038;', '&', $feedurl );

	$display             = (int) $display;
	$displaydescriptions = (int) $displaydescriptions;
	$html                = (int) $html;
	$truncatedescchar    = (int) $truncatedescchar;
	$truncatetitlechar   = (int) $truncatetitlechar;
	$date                = (int) $date;
	if ( '' === $date_format ) {
		$date_format = get_option( 'date_format' );
	}
	$creator       = (int) $creator;
	$charsetscan   = (int) $charsetscan;
	$debug         = (int) $debug;
	$paging        = (int) $paging;
	$use_simplepie = (int) $use_simplepie;
	$view          = (int) $view;
	$random_sort   = (int) $random_sort;

	if ( $use_simplepie ) {
		if ( ! class_exists( 'SimplePie' ) ) {
			if ( file_exists( ABSPATH . WPINC . '/class-simplepie.php' ) ) {
				@require ABSPATH . WPINC . '/class-simplepie.php';
			} else {
				wp_die( __( 'Error in file: '
				            . __FILE__
				            . ' on line: '
				            . __LINE__
				            . '.<br />The WordPress file "class-simplepie.php" with class SimplePie could not be included.' ) );
			}
		}
	} elseif ( file_exists( ABSPATH . WPINC . '/rss.php' ) ) {
		require_once ABSPATH . WPINC . '/rss.php';
		// It's WordPress 2.x. since it has been loaded successfully
	} elseif ( file_exists( ABSPATH . WPINC . '/rss-functions.php' ) ) {
		require_once ABSPATH . WPINC . '/rss-functions.php';
		// In WordPress < 2.1
	} else {
		wp_die( __( 'Error in file: ' . __FILE__ . ' on line: ' . __LINE__
		            . '.<br>The WordPress file "rss-functions.php" or "rss.php" could not be included.' ) );
	}

	$display           = (int) $display;
	$page              = ( ( ! empty( $_GET['rsspage'] ) && (int) $_GET['rsspage'] > 0 )
		? (int) $_GET['rsspage']
		: 1 );
	$truncatedescchar  = (int) $truncatedescchar;
	$truncatetitlechar = (int) $truncatetitlechar;
	$echo              = '<!--via MagpieRSS with RSSImport-->';
	if ( $use_simplepie ) {
		$echo = '<!--via SimplePie with RSSImport-->';
	}

	// read in file for search charset
	if ( $charsetscan && function_exists( 'file_get_contents' ) ) {
		ini_set( 'default_socket_timeout', 10 );
		$a = file_get_contents( $feedurl );
		// for better performance, if the server accepts the method
		// $a = file_get_contents( $feedurl, FALSE, NULL, 0, 50 );
	}

	if ( $use_simplepie ) {
		$rss = fetch_feed( $feedurl );
	} else {
		$rss = fetch_rss( $feedurl );
	}

	if ( $rss && ! is_wp_error( $rss ) ) {
		// the follow print_r list all items in array, for debug purpose
		if ( $debug ) {
			print '<pre>';
			print 'FeedURL: ' . $feedurl . '<br>';
			print 'The object after parsing Feed URL:<br>';
			/** @noinspection ForgottenDebugOutputInspection Debug Statement is OK here! */
			print_r( $rss );
			print '</pre>';
			if ( ! defined( 'MAGPIE_CACHE_ON' ) ) {
				define( 'MAGPIE_CACHE_ON', false );
			}
		}

		if ( isset( $target ) && '' !== $target ) {
			$strings = array( '', 'blank', 'self', 'top' );
			if ( ! in_array( $target, $strings, true ) ) {
				$target = '';
			} else {
				$target = ' target="_' .  $target . '"';
			}
		}

		if ( isset( $rel ) && '' !== $rel ) {
			$rel = ' rel="' . $rel . '"';
		}

		$displayitems  = $display;
		$displaylimit  = ( $page * $display );
		$display       = ( ( $page - 1 ) * $display );
		$picked = [];

		if ( $use_simplepie && ( 1 === $paging || $random_sort ) ) {
			/** @var SimplePie $rss */
			$items = $rss->get_items();
		} elseif ( $use_simplepie ) {
			/** @var SimplePie $rss */
			$items = $rss->get_items( 0, $displayitems );
		} else {
			/** @var MagpieRSS $rss */
			$items = $rss->items;
		}
		$cnt_items = count( $items );

		while ( $display < $displaylimit ) {
			$i = $display;
			if ( $random_sort ) {
				do {
					$i = mt_rand( 0, $cnt_items - 1 );
				} while ( in_array( $i, $picked, true ) && count( $picked ) < $cnt_items );
				$picked[] = $i;
			}

			if ( array_key_exists( $i, $items ) ) {
				if ( $use_simplepie ) {
					/** @var SimplePie $rss */
					$item = $rss->get_item( $i );
				} else {
					/** @var MagpieRSS $rss */
					$item = $rss->items[ $i ];
				}
				// import title
				$title = '';
				if ( $use_simplepie ) {
					$title = esc_attr( strip_tags( $item->get_title() ) );
				} elseif ( isset( $item['title'] ) ) {
					$title = esc_attr( strip_tags( $item['title'] ) );
				}
				// import link
				$href = '';
				if ( $use_simplepie ) {
					$href = wp_filter_kses( $item->get_link() );
				} elseif ( isset( $item['link'] ) ) {
					$href = wp_filter_kses( $item['link'] );
				}
				// import picture_url
				$picture_url = '';
				if ( $use_simplepie && $enclosure = $item->get_enclosure() ) {
					$picture_url = wp_filter_kses( $enclosure->get_thumbnail() );
				}

				$start_item_temp = str_replace( [ '%title%', '%href%', '%picture_url%' ],
					[ $title, $href, $picture_url ],
					$start_item );
				$echo            .= $start_item_temp;

				// import date
				if ( $use_simplepie && $date ) {
					$pubDate = date_i18n( $date_format, strtotime( $item->get_date() ) );
				} elseif ( $date && isset( $item['pubdate'] ) ) {
					$pubDate = date_i18n( $date_format, strtotime( $item['pubdate'] ) );
				}
				// import creator
				if ( $use_simplepie && $creator ) {
					/** @var SimplePie $item */
					$creator = ' <cite>' . esc_html( strip_tags( $item->get_author()->get_name() ) ) . '</cite>';
				} elseif ( $creator && isset( $item['dc']['creator'] ) ) {
					$creator = esc_html( $item['dc']['creator'] );
				} elseif ( $creator && isset( $item['creator'] ) ) {
					$creator = esc_html( $item['creator'] );
				}
				// import desc
				if ( $use_simplepie && $displaydescriptions && $html ) {
					$desc = @html_entity_decode( $item->get_content(), ENT_QUOTES, get_option( 'blog_charset' ) );
				} // For import with HTML
				elseif ( $use_simplepie && $displaydescriptions && ! $html ) {
					$desc = str_replace(
						[ "\n", "\r" ],
						' ',
						esc_attr( strip_tags(
								@html_entity_decode(
									$item->get_description(),
									ENT_QUOTES,
									get_option( 'blog_charset' ) ) )
						)
					);
				} // For import without HTML
				elseif ( $displaydescriptions
				         && $html
				         && isset( $item['content']['encoded'] )
				         && $item['content']['encoded'] !== 'A' ) {
					$desc = $item['content']['encoded'];
				} // For import with HTML
				elseif ( $displaydescriptions
				         && $html
				         && isset( $item['content']['atom_content'] )
				         && $item['content']['atom_content'] !== 'A' ) {
					$desc = $item['content']['atom_content'];
				} // For import with HTML
				elseif ( $displaydescriptions
				         && $html
				         && isset( $item['content'] )
				         && ! is_array( $item['content'] ) ) {
					$desc = $item['content'];
				} elseif ( $displaydescriptions && $html && isset( $item['description'] ) ) {
					$desc = $item['description'];
				} elseif ( $displaydescriptions && ! $html && isset( $item['description'] ) ) {
					$desc = esc_html( strip_tags( $item['description'] ) );
				} // For import without HTML

				if ( isset( $a ) && false !== stripos( $a, 'ISO' ) ) {
					if ( $debug ) {
						$echo .= 'ISO Feed' . "\n";
					}
					if ( isset( $title ) ) {
						isodec( $title );
					}
					if ( isset( $creator ) ) {
						isodec( $creator );
					}
					if ( isset( $desc ) ) {
						isodec( $desc );
					}
				} else {
					if ( $debug ) {
						$echo .= 'NonISO Feed' . "\n";
					}
					if ( isset( $title ) ) {
						utf8dec( $title );
					}
					if ( isset( $creator ) ) {
						utf8dec( $creator );
					}
					if ( isset( $desc ) ) {
						utf8dec( $desc );
					}
				}

				if ( isset( $title ) ) {
					all_convert( $title );
				}
				if ( isset( $creator ) ) {
					all_convert( $creator );
				}
				if ( isset( $desc ) ) {
					all_convert( $desc );
				}

				if ( isset( $title ) && $truncatetitlechar && ( strlen( $title ) > $truncatetitlechar ) ) {
					$title = wp_html_excerpt( $title, $truncatetitlechar ) . $truncatetitlestring;
				}

				if ( isset( $desc ) && $truncatedescchar && ( strlen( $desc ) > $truncatedescchar ) ) {
					$desc = wp_html_excerpt( $desc, $truncatedescchar ) . $truncatedescstring;
				}

				if ( $desc4title ) {
					$desc = '';
					if ( $use_simplepie ) {
						$desc = str_replace(
							[ "\n", "\r" ],
							' ',
							esc_attr( strip_tags(
									@html_entity_decode(
										$item->get_description(),
										ENT_QUOTES,
										get_option( 'blog_charset' )
									)
								)
							)
						);
					} // For import without HTML
                    elseif ( isset( $item['description'] ) ) {
						$desc = esc_html( strip_tags( $item['description'] ) );
					}
					$atitle = wp_html_excerpt( $desc, $truncatedescchar ) . $truncatedescstring;
				} else {
					$atitle = $title;
				}

				// Read order settings and rearrange them before output.
				$order_array = explode( ',', $order );
				foreach ( $order_array as $objects ) {
					switch ( $objects ) {
						case 'date':
							if ( isset( $pubDate ) && $date && '' !== $pubDate ) {
								$echo .= $before_date . $pubDate . $after_date;
							}
							break;
						case 'title':
							$echo .= '<a'
							         . $target
							         . $rel
							         . ' href="'
							         . $href
							         . '" title="'
							         . $atitle
							         . '">'
							         . $title
							         . '</a>';
							break;
						case 'creator':
							if ( isset( $creator ) && $creator && '' !== $creator ) {
								$echo .= $before_creator . $creator . $after_creator;
							}
							break;
						case 'description':
							if ( isset( $desc ) && $displaydescriptions && '' !== $desc ) {
								$after_desc_temp  = stripslashes_deep( $after_desc );
								$after_desc_temp  = str_replace( [ '%title%', '%href%', '%picture_url%' ],
									[ $title, $href, $picture_url ],
									$after_desc_temp );
								$before_desc_temp = str_replace( [ '%title%', '%href%', '%picture_url%' ],
									[ $title, $href, $picture_url ],
									$before_desc );
								$echo             .= $before_desc_temp . $desc . $after_desc_temp;
							}
							break;
					}
				}

				$end_item_temp = str_replace( [ '%title%', '%href%', '%picture_url%' ],
					[ $title, $href, $picture_url ],
					$end_item );
				$echo          .= $end_item_temp;
			}

			$display ++;
		}

		if ( strip_tags( $echo ) ) { // novaclic: needed to filter out false content made of tags alone (html comments, html tags, ...)
			$echo = wptexturize( $start_items . $echo . $end_items );
		} else {
			$echo = wptexturize( $before_noitems . $noitems . $after_noitems );
		}
	} else {
		/** @noinspection MissingIssetImplementationInspection */
		if ( empty( $rss->ERROR ) ) {
			$rss->ERROR = null;
		}
		$echo = wptexturize( $before_error . $error . $rss->ERROR . $after_error );
	}

	if ( $paging ) {
		$nextitems     = true;
		$previousitems = false;
		if ( $page > 1 ) {
			$previousitems = true;
		}
		$echo .= '<div class="rsspaging">';
		if ( $previousitems ) {
			$echo .= '<a href="'
			         . add_query_arg( 'rsspage',
					( $page - 1 ) )
			         . '" class="rsspaging_prev" title="'
			         . $prev_paging_title
			         . '">'
			         . $prev_paging_link
			         . '</a>';
		}
		if ( $nextitems ) {
			$echo .= '<a href="'
			         . add_query_arg( 'rsspage',
					( $page + 1 ) )
			         . '" class="rsspaging_next" title="'
			         . $next_paging_title
			         . '">'
			         . $next_paging_link
			         . '</a>';
		}
		$echo .= '<br style="clear: both" />';
		$echo .= '</div>';
	}

	if ( $view ) {
		echo $echo;
	}
	return $echo;
}

/**
 * @param $s_String
 *
 * @return false|string
 */
function utf8dec( $s_String ) {
	if ( PHP_VERSION_ID >= 50000 ) {
		$s_String = html_entity_decode(
			htmlentities( $s_String . ' ', ENT_COMPAT, 'UTF-8' )
		);
	} else {
		$s_String = RSSImport_html_entity_decode_php4( htmlentities( $s_String . ' ' ) );
	}

	return substr( $s_String, 0, - 1 );
}

/**
 * @param $s_String
 *
 * @return false|string
 */
function isodec( $s_String ) {
	if ( PHP_VERSION_ID >= 50000 ) {
		$s_String = html_entity_decode( htmlentities( $s_String . ' ', ENT_COMPAT, 'ISO-8859-1' ) );
	} else {
		$s_String = RSSImport_html_entity_decode_php4( htmlentities( $s_String . ' ' ) );
	}

	return substr( $s_String, 0, - 1 );
}

/**
 * @param $s_String
 *
 * @return string|string[]|null
 */
function all_convert( $s_String ) {
	// Array for entities
	$umlaute = [
		'â€ž',
		'â€œ',
		'â€“',
		' \&#34;',
		'&#8211;',
		'&#8212;',
		'&#8216;',
		'&#8217;',
		'&#8220;',
		'&#8221;',
		'&#8222;',
		'&#8226;',
		'&#8230;',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'¡',
		'¢',
		'£',
		'¤',
		'¥',
		'¦',
		'§',
		'¨',
		'©',
		'ª',
		'«',
		'¬',
		'®',
		'¯',
		'°',
		'±',
		'²',
		'³',
		'´',
		'µ',
		'¶',
		'·',
		'¸',
		'¹',
		'º',
		'»',
		'¼',
		'½',
		'¾',
		'¿',
		'À',
		'Á',
		'Â',
		'Ã',
		'Ä',
		'Å',
		'Æ',
		'Ç',
		'È',
		'É',
		'Ê',
		'Ë',
		'Ì',
		'Í',
		'Î',
		'Ï',
		'Ð',
		'Ñ',
		'Ò',
		'Ó',
		'Ô',
		'Õ',
		'Ö',
		'×',
		'Ø',
		'Ù',
		'Ú',
		'Û',
		'Ü',
		'Ý',
		'Þ',
		'ß',
		'à',
		'á',
		'â',
		'ã',
		'ä',
		'å',
		'æ',
		'ç',
		'è',
		'é',
		'ê',
		'ë',
		'ì',
		'í',
		'î',
		'ï',
		'ð',
		'ñ',
		'ò',
		'ó',
		'ô',
		'õ',
		'ö',
		'÷',
		'ø',
		'ù',
		'ú',
		'û',
		'ü',
		'ý',
		'þ',
		'ÿ',
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '' ),
		utf8_encode( '¡' ),
		utf8_encode( '¢' ),
		utf8_encode( '£' ),
		utf8_encode( '¤' ),
		utf8_encode( '¥' ),
		utf8_encode( '¦' ),
		utf8_encode( '§' ),
		utf8_encode( '¨' ),
		utf8_encode( '©' ),
		utf8_encode( 'ª' ),
		utf8_encode( '«' ),
		utf8_encode( '¬' ),
		utf8_encode( '®' ),
		utf8_encode( '¯' ),
		utf8_encode( '°' ),
		utf8_encode( '±' ),
		utf8_encode( '²' ),
		utf8_encode( '³' ),
		utf8_encode( '´' ),
		utf8_encode( 'µ' ),
		utf8_encode( '¶' ),
		utf8_encode( '·' ),
		utf8_encode( '¸' ),
		utf8_encode( '¹' ),
		utf8_encode( 'º' ),
		utf8_encode( '»' ),
		utf8_encode( '¼' ),
		utf8_encode( '½' ),
		utf8_encode( '¾' ),
		utf8_encode( '¿' ),
		utf8_encode( 'À' ),
		utf8_encode( 'Á' ),
		utf8_encode( 'Â' ),
		utf8_encode( 'Ã' ),
		utf8_encode( 'Ä' ),
		utf8_encode( 'Å' ),
		utf8_encode( 'Æ' ),
		utf8_encode( 'Ç' ),
		utf8_encode( 'È' ),
		utf8_encode( 'É' ),
		utf8_encode( 'Ê' ),
		utf8_encode( 'Ë' ),
		utf8_encode( 'Ì' ),
		utf8_encode( 'Í' ),
		utf8_encode( 'Î' ),
		utf8_encode( 'Ï' ),
		utf8_encode( 'Ð' ),
		utf8_encode( 'Ñ' ),
		utf8_encode( 'Ò' ),
		utf8_encode( 'Ó' ),
		utf8_encode( 'Ô' ),
		utf8_encode( 'Õ' ),
		utf8_encode( 'Ö' ),
		utf8_encode( '×' ),
		utf8_encode( 'Ø' ),
		utf8_encode( 'Ù' ),
		utf8_encode( 'Ú' ),
		utf8_encode( 'Û' ),
		utf8_encode( 'Ü' ),
		utf8_encode( 'Ý' ),
		utf8_encode( 'Þ' ),
		utf8_encode( 'ß' ),
		utf8_encode( 'à' ),
		utf8_encode( 'á' ),
		utf8_encode( 'â' ),
		utf8_encode( 'ã' ),
		utf8_encode( 'ä' ),
		utf8_encode( 'å' ),
		utf8_encode( 'æ' ),
		utf8_encode( 'ç' ),
		utf8_encode( 'è' ),
		utf8_encode( 'é' ),
		utf8_encode( 'ê' ),
		utf8_encode( 'ë' ),
		utf8_encode( 'ì' ),
		utf8_encode( 'í' ),
		utf8_encode( 'î' ),
		utf8_encode( 'ï' ),
		utf8_encode( 'ð' ),
		utf8_encode( 'ñ' ),
		utf8_encode( 'ò' ),
		utf8_encode( 'ó' ),
		utf8_encode( 'ô' ),
		utf8_encode( 'õ' ),
		utf8_encode( 'ö' ),
		utf8_encode( '÷' ),
		utf8_encode( 'ø' ),
		utf8_encode( 'ù' ),
		utf8_encode( 'ú' ),
		utf8_encode( 'û' ),
		utf8_encode( 'ü' ),
		utf8_encode( 'ý' ),
		utf8_encode( 'þ' ),
		utf8_encode( 'ÿ' ),
		chr( 128 ),
		chr( 129 ),
		chr( 130 ),
		chr( 131 ),
		chr( 132 ),
		chr( 133 ),
		chr( 134 ),
		chr( 135 ),
		chr( 136 ),
		chr( 137 ),
		chr( 138 ),
		chr( 139 ),
		chr( 140 ),
		chr( 141 ),
		chr( 142 ),
		chr( 143 ),
		chr( 144 ),
		chr( 145 ),
		chr( 146 ),
		chr( 147 ),
		chr( 148 ),
		chr( 149 ),
		chr( 150 ),
		chr( 151 ),
		chr( 152 ),
		chr( 153 ),
		chr( 154 ),
		chr( 155 ),
		chr( 156 ),
		chr( 157 ),
		chr( 158 ),
		chr( 159 ),
		chr( 160 ),
		chr( 161 ),
		chr( 162 ),
		chr( 163 ),
		chr( 164 ),
		chr( 165 ),
		chr( 166 ),
		chr( 167 ),
		chr( 168 ),
		chr( 169 ),
		chr( 170 ),
		chr( 171 ),
		chr( 172 ),
		chr( 173 ),
		chr( 174 ),
		chr( 175 ),
		chr( 176 ),
		chr( 177 ),
		chr( 178 ),
		chr( 179 ),
		chr( 180 ),
		chr( 181 ),
		chr( 182 ),
		chr( 183 ),
		chr( 184 ),
		chr( 185 ),
		chr( 186 ),
		chr( 187 ),
		chr( 188 ),
		chr( 189 ),
		chr( 190 ),
		chr( 191 ),
		chr( 192 ),
		chr( 193 ),
		chr( 194 ),
		chr( 195 ),
		chr( 196 ),
		chr( 197 ),
		chr( 198 ),
		chr( 199 ),
		chr( 200 ),
		chr( 201 ),
		chr( 202 ),
		chr( 203 ),
		chr( 204 ),
		chr( 205 ),
		chr( 206 ),
		chr( 207 ),
		chr( 208 ),
		chr( 209 ),
		chr( 210 ),
		chr( 211 ),
		chr( 212 ),
		chr( 213 ),
		chr( 214 ),
		chr( 215 ),
		chr( 216 ),
		chr( 217 ),
		chr( 218 ),
		chr( 219 ),
		chr( 220 ),
		chr( 221 ),
		chr( 222 ),
		chr( 223 ),
		chr( 224 ),
		chr( 225 ),
		chr( 226 ),
		chr( 227 ),
		chr( 228 ),
		chr( 229 ),
		chr( 230 ),
		chr( 231 ),
		chr( 232 ),
		chr( 233 ),
		chr( 234 ),
		chr( 235 ),
		chr( 236 ),
		chr( 237 ),
		chr( 238 ),
		chr( 239 ),
		chr( 240 ),
		chr( 241 ),
		chr( 242 ),
		chr( 243 ),
		chr( 244 ),
		chr( 245 ),
		chr( 246 ),
		chr( 247 ),
		chr( 248 ),
		chr( 249 ),
		chr( 250 ),
		chr( 251 ),
		chr( 252 ),
		chr( 253 ),
		chr( 254 ),
		chr( 255 ),
		chr( 256 ),
	];

	$htmlcode = [
		'&bdquo;',
		'&ldquo;',
		'&ndash;',
		' &#34;',
		'&ndash;',
		'&mdash;',
		'&lsquo;',
		'&rsquo;',
		'&ldquo;',
		'&rdquo;',
		'&bdquo;',
		'&bull;',
		'&hellip;',
		'&euro;',
		'&sbquo;',
		'&fnof;',
		'&bdquo;',
		'&hellip;',
		'&dagger;',
		'&Dagger;',
		'&circ;',
		'&permil;',
		'&Scaron;',
		'&lsaquo;',
		'&OElig;',
		'&#x017D;',
		'&lsquo;',
		'&rsquo;',
		'&ldquo;',
		'&rdquo;',
		'&bull;',
		'&ndash;',
		'&mdash;',
		'&tilde;',
		'&trade;',
		'&scaron;',
		'&rsaquo;',
		'&oelig;',
		'&#x017E;',
		'&Yuml;',
		'&iexcl;',
		'&cent;',
		'&pound;',
		'&curren;',
		'&yen;',
		'&brvbar;',
		'&sect;',
		'&uml;',
		'&copy;',
		'&ordf;',
		'&laquo;',
		'&not;',
		'&reg;',
		'&macr;',
		'&deg;',
		'&plusmn;',
		'&sup2;',
		'&sup3;',
		'&acute;',
		'&micro;',
		'&para;',
		'&middot;',
		'&cedil;',
		'&supl;',
		'&ordm;',
		'&raquo;',
		'&frac14;',
		'&frac12;',
		'&frac34;',
		'&iquest;',
		'&Agrave;',
		'&Aacute;',
		'&Acirc;',
		'&Atilde;',
		'&Auml;',
		'&Aring;',
		'&AElig;',
		'&Ccedil;',
		'&Egrave;',
		'&Eacute;',
		'&Ecirc;',
		'&Euml;',
		'&Igrave;',
		'&Iacute;',
		'&Icirc;',
		'&Iuml;',
		'&ETH;',
		'&Ntilde;',
		'&Ograve;',
		'&Oacute;',
		'&Ocirc;',
		'&Otilde;',
		'&Ouml;',
		'&times;',
		'&Oslash;',
		'&Ugrave;',
		'&Uacute;',
		'&Ucirc;',
		'&Uuml;',
		'&Yacute;',
		'&THORN;',
		'&szlig;',
		'&agrave;',
		'&aacute;',
		'&acirc;',
		'&atilde;',
		'&auml;',
		'&aring;',
		'&aelig;',
		'&ccedil;',
		'&egrave;',
		'&eacute;',
		'&ecirc;',
		'&euml;',
		'&igrave;',
		'&iacute;',
		'&icirc;',
		'&iuml;',
		'&eth;',
		'&ntilde;',
		'&ograve;',
		'&oacute;',
		'&ocirc;',
		'&otilde;',
		'&ouml;',
		'&divide;',
		'&oslash;',
		'&ugrave;',
		'&uacute;',
		'&ucirc;',
		'&uuml;',
		'&yacute;',
		'&thorn;',
		'&yuml;',
		'&euro;',
		'&sbquo;',
		'&fnof;',
		'&bdquo;',
		'&hellip;',
		'&dagger;',
		'&Dagger;',
		'&circ;',
		'&permil;',
		'&Scaron;',
		'&lsaquo;',
		'&OElig;',
		'&#x017D;',
		'&lsquo;',
		'&rsquo;',
		'&ldquo;',
		'&rdquo;',
		'&bull;',
		'&ndash;',
		'&mdash;',
		'&tilde;',
		'&trade;',
		'&scaron;',
		'&rsaquo;',
		'&oelig;',
		'&#x017E;',
		'&Yuml;',
		'&iexcl;',
		'&cent;',
		'&pound;',
		'&curren;',
		'&yen;',
		'&brvbar;',
		'&sect;',
		'&uml;',
		'&copy;',
		'&ordf;',
		'&laquo;',
		'&not;',
		'&reg;',
		'&macr;',
		'&deg;',
		'&plusmn;',
		'&sup2;',
		'&sup3;',
		'&acute;',
		'&micro;',
		'&para;',
		'&middot;',
		'&cedil;',
		'&supl;',
		'&ordm;',
		'&raquo;',
		'&frac14;',
		'&frac12;',
		'&frac34;',
		'&iquest;',
		'&Agrave;',
		'&Aacute;',
		'&Acirc;',
		'&Atilde;',
		'&Auml;',
		'&Aring;',
		'&AElig;',
		'&Ccedil;',
		'&Egrave;',
		'&Eacute;',
		'&Ecirc;',
		'&Euml;',
		'&Igrave;',
		'&Iacute;',
		'&Icirc;',
		'&Iuml;',
		'&ETH;',
		'&Ntilde;',
		'&Ograve;',
		'&Oacute;',
		'&Ocirc;',
		'&Otilde;',
		'&Ouml;',
		'&times;',
		'&Oslash;',
		'&Ugrave;',
		'&Uacute;',
		'&Ucirc;',
		'&Uuml;',
		'&Yacute;',
		'&THORN;',
		'&szlig;',
		'&agrave;',
		'&aacute;',
		'&acirc;',
		'&atilde;',
		'&auml;',
		'&aring;',
		'&aelig;',
		'&ccedil;',
		'&egrave;',
		'&eacute;',
		'&ecirc;',
		'&euml;',
		'&igrave;',
		'&iacute;',
		'&icirc;',
		'&iuml;',
		'&eth;',
		'&ntilde;',
		'&ograve;',
		'&oacute;',
		'&ocirc;',
		'&otilde;',
		'&ouml;',
		'&divide;',
		'&oslash;',
		'&ugrave;',
		'&uacute;',
		'&ucirc;',
		'&uuml;',
		'&yacute;',
		'&thorn;',
		'&yuml;',
		'&euro;',
		'',
		'&sbquo;',
		'&fnof;',
		'&bdquo;',
		'&hellip;',
		'&dagger;',
		'&Dagger;',
		'&circ;',
		'&permil;',
		'&Scaron;',
		'&lsaquo;',
		'&OElig;',
		'',
		'&#x017D;',
		'',
		'',
		'&lsquo;',
		'&rsquo;',
		'&ldquo;',
		'&rdquo;',
		'&bull;',
		'&ndash;',
		'&mdash;',
		'&tilde;',
		'&trade;',
		'&scaron;',
		'&rsaquo;',
		'&oelig;',
		'',
		'&#x017E;',
		'&Yuml;',
		'&nbsp;',
		'&iexcl;',
		'&iexcl;',
		'&iexcl;',
		'&iexcl;',
		'&yen;',
		'&brvbar;',
		'&sect;',
		'&uml;',
		'&copy;',
		'&ordf;',
		'&laquo;',
		'&not;',
		'­&shy;',
		'&reg;',
		'&macr;',
		'&deg;',
		'&plusmn;',
		'&sup2;',
		'&sup3;',
		'&acute;',
		'&micro;',
		'&para;',
		'&middot;',
		'&cedil;',
		'&supl;',
		'&ordm;',
		'&raquo;',
		'&frac14;',
		'&frac12;',
		'&frac34;',
		'&iquest;',
		'&Agrave;',
		'&Aacute;',
		'&Acirc;',
		'&Atilde;',
		'&Auml;',
		'&Aring;',
		'&AElig;',
		'&Ccedil;',
		'&Egrave;',
		'&Eacute;',
		'&Ecirc;',
		'&Euml;',
		'&Igrave;',
		'&Iacute;',
		'&Icirc;',
		'&Iuml;',
		'&ETH;',
		'&Ntilde;',
		'&Ograve;',
		'&Oacute;',
		'&Ocirc;',
		'&Otilde;',
		'&Ouml;',
		'&times;',
		'&Oslash;',
		'&Ugrave;',
		'&Uacute;',
		'&Ucirc;',
		'&Uuml;',
		'&Yacute;',
		'&THORN;',
		'&szlig;',
		'&agrave;',
		'&aacute;',
		'&acirc;',
		'&atilde;',
		'&auml;',
		'&aring;',
		'&aelig;',
		'&ccedil;',
		'&egrave;',
		'&eacute;',
		'&ecirc;',
		'&euml;',
		'&igrave;',
		'&iacute;',
		'&icirc;',
		'&iuml;',
		'&eth;',
		'&ntilde;',
		'&ograve;',
		'&oacute;',
		'&ocirc;',
		'&otilde;',
		'&ouml;',
		'&divide;',
		'&oslash;',
		'&ugrave;',
		'&uacute;',
		'&ucirc;',
		'&uuml;',
		'&yacute;',
		'&thorn;',
		'&yuml;',
	];

	if ( PHP_VERSION_ID >= 50000 ) {
		$s_String = utf8_encode( html_entity_decode( str_replace( $umlaute, $htmlcode, $s_String ) ) );
	} else {
		$s_String = utf8_encode( RSSImport_html_entity_decode_php4( str_replace( $umlaute, $htmlcode, $s_String ) ) );
	}

	// &hellip; , &#8230;
	$s_String = preg_replace( '~\xC3\xA2\xE2\x82\xAC\xC2\xA6~', '&hellip;', $s_String );
	$s_String = preg_replace( '~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\x82\xC2\xA6~', '&hellip;', $s_String );
	$s_String = preg_replace( '~\xD0\xB2\xD0\x82\xC2\xA6~', '&hellip;', $s_String );

	// &mdash; , &#8212;
	$s_String = preg_replace( '~\xC3\xA2\xE2\x82\xAC\xE2\x80\x9D~', '&mdash;', $s_String );
	$s_String = preg_replace( '~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\xA2\xE2\x82\xAC\xC2\x9D~',
		'&mdash;',
		$s_String );
	$s_String = preg_replace( '~\xD0\xB2\xD0\x82\xE2\x80\x9D~', '&mdash;', $s_String );

	// &ndash; , &#8211;
	$s_String = preg_replace( '~\xC3\xA2\xE2\x82\xAC\xE2\x80\x9C~', '&ndash;', $s_String );
	$s_String = preg_replace( '~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\xA2\xE2\x82\xAC\xC5\x93~',
		'&ndash;',
		$s_String );
	$s_String = preg_replace( '~\xD0\xB2\xD0\x82\xE2\x80\x9C~', '&ndash;', $s_String );

	// &rsquo; , &#8217;
	$s_String = preg_replace( '~\xC3\xA2\xE2\x82\xAC\xE2\x84\xA2~', '&rsquo;', $s_String );
	$s_String = preg_replace( '~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\xA2\xE2\x80\x9E\xC2\xA2~',
		'&rsquo;',
		$s_String );
	$s_String = preg_replace( '~\xD0\xB2\xD0\x82\xE2\x84\xA2~', '&rsquo;', $s_String );
	$s_String = preg_replace( '~\xD0\xBF\xD1\x97\xD0\x85~', '&rsquo;', $s_String );

	// &lsquo; , &#8216;
	$s_String = preg_replace( '~\xC3\xA2\xE2\x82\xAC\xCB\x9C~', '&lsquo;', $s_String );
	$s_String = preg_replace( '~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\x8B\xC5\x93~', '&lsquo;', $s_String );

	// &rdquo; , &#8221;
	$s_String = preg_replace( '~\xC3\xA2\xE2\x82\xAC\xC2\x9D~', '&rdquo;', $s_String );
	$s_String = preg_replace( '~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\x82\xC2\x9D~', '&rdquo;', $s_String );
	$s_String = preg_replace( '~\xD0\xB2\xD0\x82\xD1\x9C~', '&rdquo;', $s_String );

	// &ldquo; , &#8220;
	$s_String = preg_replace( '~\xC3\xA2\xE2\x82\xAC\xC5\x93~', '&ldquo;', $s_String );
	$s_String = preg_replace( '~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\x85\xE2\x80\x9C~',
		'&ldquo;',
		$s_String );
	$s_String = preg_replace( '~\xD0\xB2\xD0\x82\xD1\x9A~', '&ldquo;', $s_String );

	// &trade; , &#8482;
	$s_String = preg_replace( '~\xC3\xA2\xE2\x80\x9E\xC2\xA2~', '&trade;', $s_String );
	$s_String = preg_replace( '~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x82\xAC\xC5\xBE\xC3\x82\xC2\xA2~', '&trade;', $s_String );

	// th
	$s_String = preg_replace( '~t\xC3\x82\xC2\xADh~', 'th', $s_String );

	// .
	$s_String = preg_replace( '~.\xD0\x92+~', '.', $s_String );
	$s_String = preg_replace( '~.\xD0\x92~', '.', $s_String );

	// ,
	$s_String = preg_replace( '~\x2C\xD0\x92~', ',', $s_String );

	return $s_String;
}

/**
 * Entfernt unvollstaendige Worte am Ende eines Strings.
 *
 * @author Thomas Scholz <http://toscho.de>
 *
 * @param $str
 *
 * @return string
 */
function RSSImport_end_on_word( $str ) {
	$arr = explode( ' ', trim( $str ) );
	array_pop( $arr );

	return rtrim( implode( ' ', $arr ), ',;' );
}

/**
 * @param array $atts
 *
 * @return string
 */
function RSSImport_Shortcode( $atts ) {
	$args = shortcode_atts(
		[
			'display'             => 5,
			'feedurl'             => 'https://bueltge.de/feed/',
			'before_desc'         => '<br />',
			'displaydescriptions' => 0,
			'after_desc'          => '',
			'html'                => 0,
			'truncatedescchar'    => 200,
			'truncatedescstring'  => ' ... ',
			'truncatetitlechar'   => '',
			'truncatetitlestring' => ' ... ',
			'before_date'         => ' <small>',
			'date'                => 0,
			'after_date'          => '</small>',
			'date_format'         => '',
			'before_creator'      => ' <small>',
			'creator'             => 0,
			'after_creator'       => '</small>',
			'start_items'         => '<ul>',
			'end_items'           => '</ul>',
			'start_item'          => '<li>',
			'end_item'            => '</li>',
			'target'              => '',
			'rel'                 => '',
			'desc4title'          => 0,
			'charsetscan'         => 0,
			'debug'               => 0,
			'before_noitems'      => '<p>',
			'noitems'             => __( 'No items, feed is empty.', FB_RSSI_TEXTDOMAIN ),
			'after_noitems'       => '</p>',
			'before_error'        => '<p>',
			'error'               => __( 'Error: Feed has an error or is not valid.',
				FB_RSSI_TEXTDOMAIN ),
			'after_error'         => '</p>',
			'paging'              => 0,
			'prev_paging_link'    => __( '&laquo; Previous', FB_RSSI_TEXTDOMAIN ),
			'next_paging_link'    => __( 'Next &raquo;', FB_RSSI_TEXTDOMAIN ),
			'prev_paging_title'   => __( 'more items', FB_RSSI_TEXTDOMAIN ),
			'next_paging_title'   => __( 'more items', FB_RSSI_TEXTDOMAIN ),
			'use_simplepie'       => 1,
			'view'                => 0,
			'random_sort'         => 0,
			'order'               => 'date,title,creator,description',
		],
		$atts
	);

	$display = $args['display'];
	$feedurl = html_entity_decode( $args['feedurl'] ); // novaclic: undo encoding due to wordpress WYSIWYG editor
	if ( 'true' === strtolower( $args['html'] ) ) {
		$args['html'] = 1;
	}
	$before_desc = $args['before_desc'];
	$html        = $args['html'];
	if ( 'true' === strtolower( $args['displaydescriptions'] ) ) {
		$args['displaydescriptions'] = 1;
	}
	$after_desc          = $args['after_desc'];
	$displaydescriptions = $args['displaydescriptions'];
	if ( 'true' === strtolower( $args['truncatedescchar'] ) ) {
		$args['truncatedescchar'] = 1;
	}
	$truncatedescchar = $args['truncatedescchar'];
	if ( 'true' === strtolower( $args['truncatetitlechar'] ) ) {
		$args['truncatetitlechar'] = 1;
	}
	$truncatedescstring  = $args['truncatedescstring'];
	$truncatetitlestring = $args['truncatetitlestring'];
	$truncatetitlechar   = $args['truncatetitlechar'];
	$before_date         = $args['before_date'];
	if ( 'true' === strtolower( $args['date'] ) ) {
		$args['date'] = 1;
	}
	$date        = $args['date'];
	$after_date  = $args['after_date'];
	$date_format = $args['date_format'];
	if ( 'true' === strtolower( $args['creator'] ) ) {
		$args['creator'] = 1;
	}
	$before_creator = $args['before_creator'];
	$creator        = $args['creator'];
	$after_creator  = $args['after_creator'];
	$start_items    = $args['start_items'];
	$end_items      = $args['end_items'];
	$start_item     = $args['start_item'];
	$end_item       = $args['end_item'];
	$target         = $args['target'];
	$rel            = $args['rel'];
	$desc4title     = $args['desc4title'];
	if ( 'true' === strtolower( $args['charsetscan'] ) ) {
		$args['charsetscan'] = 1;
	}
	$charsetscan = $args['charsetscan'];
	if ( 'true' === strtolower( $args['debug'] ) ) {
		$args['debug'] = 1;
	}
	$debug          = $args['debug'];
	$before_noitems = $args['before_noitems'];
	$noitems        = $args['noitems'];
	$after_noitems  = $args['after_noitems'];
	$before_error   = $args['before_error'];
	$error          = $args['error'];
	$after_error    = $args['after_error'];
	if ( 'true' === strtolower( $args['paging'] ) ) {
		$args['paging'] = 1;
	}
	$paging = $args['paging'];
	if ( 'true' === strtolower( $args['use_simplepie'] ) ) {
		$args['use_simplepie'] = 1;
	}
	$prev_paging_link  = $args['prev_paging_link'];
	$next_paging_link  = $args['next_paging_link'];
	$prev_paging_title = $args['prev_paging_title'];
	$next_paging_title = $args['next_paging_title'];
	$use_simplepie     = $args['use_simplepie'];
	$view              = $args['view'];
	$random_sort       = $args['random_sort'];
	$order             = $args['order'];

	return RSSImport(
		$display,
		$feedurl,
		$before_desc,
		$displaydescriptions,
		$after_desc,
		$html,
		$truncatedescchar,
		$truncatedescstring,
		$truncatetitlechar,
		$truncatetitlestring,
		$before_date,
		$date,
		$after_date,
		$date_format,
		$before_creator,
		$creator,
		$after_creator,
		$start_items,
		$end_items,
		$start_item,
		$end_item,
		$target,
		$rel,
		$desc4title,
		$charsetscan,
		$debug,
		$before_noitems,
		$noitems,
		$after_noitems,
		$before_error,
		$error,
		$after_error,
		$paging,
		$prev_paging_link,
		$next_paging_link,
		$prev_paging_title,
		$next_paging_title,
		$use_simplepie,
		$view,
		$random_sort,
		$order
	);
}

/**
 * @param $pee
 *
 * @return string|string[]|null
 */
function RSSImport_shortcode_quot( $pee ) {
	global $shortcode_tags;

	if ( ! empty( $shortcode_tags ) && is_array( $shortcode_tags ) ) {
		$tagnames  = array_keys( $shortcode_tags );
		$tagregexp = implode( '|', array_map( 'preg_quote', $tagnames ) );
		$pee       = preg_replace(
			'/\\s*?(\\[(' . $tagregexp . ')\\b.*?\\/?](?:.+?\\[\\/\\2])?)\\s*/s',
			'$1',
			$pee
		);
	}

	return $pee;
}

/**
 * Add Quicktag-button to editor.
 */
function RSSImport_insert_button() {
	global $pagenow;

	$post_page_pages = [ 'post-new.php', 'post.php', 'page-new.php', 'page.php' ];
	if ( ! in_array( $pagenow, $post_page_pages, true ) ) {
		return;
	}

	?>
    <script type="text/javascript" charset="utf-8">
        /* Adding Quicktag buttons to the editor WordPress ver. 3.3 and above
		 * - Button HTML ID (required)
		 * - Button display, value="" attribute (required)
		 * - Opening Tag (required)
		 * - Closing Tag (required)
		 * - Access key, accesskey="" attribute for the button (optional)
		 * - Title, title="" attribute (optional)
		 * - Priority/position on bar, 1-9 = first, 11-19 = second, 21-29 = third, etc. (optional)
		 */
        var id = 'rssimport',
            text = '<?php _e( 'RSSImport', FB_RSSI_TEXTDOMAIN ); ?>',
            start = '[RSSImport display="5" feedurl="http://feedurl.com/" before_desc="<br />" displaydescriptions="TRUE" after_desc=" " ' +
                'html="FALSE" truncatedescchar="200" truncatedescstring=" ... " truncatetitlechar="" truncatetitlestring=" ... " ' +
                'before_date=" <small>" date="FALSE" after_date="</small>" date_format="" before_creator=" <small>" creator="FALSE" ' +
                'after_creator="</small>" start_items="<ul>" end_items="</ul>" start_item="<li>" end_item="</li>" target="" rel="" ' +
                'desc4title="" charsetscan="FALSE" debug="FALSE" before_noitems="<p>" noitems="No items, feed is empty." ' +
                'after_noitems="</p>" before_error="<p>" error="Error: Feed has an error or is not valid" after_error="</p>" ' +
                'paging="FALSE" prev_paging_link="&laquo; Previous" next_paging_link="Next &raquo;" prev_paging_title="more items" ' +
                'next_paging_title="more items" use_simplepie="FALSE" random_sort="FALSE"]',
            end = '',
            access = 'r',
            title = '<?php _e( 'Import a feed with RSSImport', FB_RSSI_TEXTDOMAIN ); ?>';

        QTags.addButton(id, text, start, end, access);
    </script>
	<?php
}

/**
 *
 */
function RSSImport_insert_button_old() {
	global $pagenow;

	$post_page_pages = [ 'post-new.php', 'post.php', 'page-new.php', 'page.php' ];
	if ( ! in_array( $pagenow, $post_page_pages, true ) ) {
		return;
	}

	echo '
	<script type="text/javascript">
		//<![CDATA[
		if ( typeof edButtons !== \'undefined\' ) {
			
			var length = edButtons.length,
			content;
			
			edButtons[length] = new edButton(
				\'RSSImport\', \'$context\', \'[RSSImport display="5" feedurl="http://feedurl.com/"'
	     .
	     ' before_desc="<br />" displaydescriptions="TRUE" after_desc=" " html="FALSE" truncatedescchar="200" truncatedescstring=" ... "'
	     .
	     ' truncatetitlechar=" " truncatetitlestring=" ... " before_date=" <small>" date="FALSE" after_date="</small>"'
	     .
	     ' date_format="" before_creator=" <small>" creator="FALSE" after_creator="</small>" start_items="<ul>" end_items="</ul>"'
	     .
	     ' start_item="<li>" end_item="</li>" target="" rel="" desc4title="" charsetscan="FALSE" debug="FALSE" before_noitems="<p>"'
	     .
	     ' noitems="No items, feed is empty." after_noitems="</p>" before_error="<p>" error="Error: Feed has an error or is not valid"'
	     .
	     ' after_error="</p>" paging="FALSE" prev_paging_link="&laquo; Previous" next_paging_link="Next &raquo;"'
	     .
	     ' prev_paging_title="more items" next_paging_title="more items" use_simplepie="FALSE"]\', \'\', \'\'
			);
			
			function RSSImport_tag( id ) {
				id = id.replace( /RSSImport_/, \'\' );
				edInsertTag( edCanvas, id );
			}
			jQuery( document ).ready( function() {
				content = \'<input id="RSSImport_\' + length + \'" class="ed_button" type="button" value="'
	     . __( 'RSSImport', FB_RSSI_TEXTDOMAIN )
	     . '" title="'
	     . __( 'Import a feed with RSSImport', FB_RSSI_TEXTDOMAIN )
	     . '" onclick="RSSImport_tag(this.id);" />\';
				jQuery( "#ed_toolbar" ).append( content );
			} );
		}
		//]]>
	</script>';
}

if ( FB_RSSI_QUICKTAG && is_admin() ) {
	if ( version_compare( $GLOBALS['wp_version'], '3.3alpha', '>=' ) ) {
		add_action( 'admin_print_footer_scripts', 'RSSImport_insert_button' );
	} else {
		add_action( 'admin_footer', 'RSSImport_insert_button_old' );
	}
}

if ( function_exists( 'add_shortcode' ) ) {
	add_shortcode( 'RSSImport', 'RSSImport_Shortcode' );
}

add_action( 'init', 'RSSImport_textdomain' );

/**
 * code to utf-8 in PHP 4
 *
 * @param string $num
 *
 * @return string
 * @package WP-RSSImport
 */
function RSSImport_code_to_utf8( $num ) {
	if ( $num <= 0x7F ) {
		return chr( $num );
	}

	if ( $num <= 0x7FF ) {
		return chr( ( $num >> 0x06 ) + 0xC0 ) . chr( ( $num & 0x3F ) + 128 );
	}

	if ( $num <= 0xFFFF ) {
		return chr( ( $num >> 0x0C ) + 0xE0 ) .
		       chr( ( ( $num >> 0x06 ) & 0x3F ) + 0x80 ) .
		       chr( ( $num & 0x3F ) + 0x80 );
	}

	if ( $num <= 0x1FFFFF ) {
		return chr( ( $num >> 0x12 ) + 0xF0 )
		       . chr( ( ( $num >> 0x0C ) & 0x3F ) + 0x80 )
		       . chr( ( ( $num >> 0x06 )
		                & 0x3F ) + 0x80 )
		       . chr( ( $num & 0x3F ) + 0x80 );
	}

	return '';
}

/**
 * html_entity_decode for PHP 4
 *
 * @param string $str
 *
 * @return string|string[]|null
 * @package WP-RSSImport
 */
function RSSImport_html_entity_decode_php4( $str ) {
	$htmlentities = [
		'&Aacute;'   => chr( 195 ) . chr( 129 ),
		'&aacute;'   => chr( 195 ) . chr( 161 ),
		'&Acirc;'    => chr( 195 ) . chr( 130 ),
		'&acirc;'    => chr( 195 ) . chr( 162 ),
		'&acute;'    => chr( 194 ) . chr( 180 ),
		'&AElig;'    => chr( 195 ) . chr( 134 ),
		'&aelig;'    => chr( 195 ) . chr( 166 ),
		'&Agrave;'   => chr( 195 ) . chr( 128 ),
		'&agrave;'   => chr( 195 ) . chr( 160 ),
		'&alefsym;'  => chr( 226 ) . chr( 132 ) . chr( 181 ),
		'&Alpha;'    => chr( 206 ) . chr( 145 ),
		'&alpha;'    => chr( 206 ) . chr( 177 ),
		'&amp;'      => chr( 38 ),
		'&and;'      => chr( 226 ) . chr( 136 ) . chr( 167 ),
		'&ang;'      => chr( 226 ) . chr( 136 ) . chr( 160 ),
		'&Aring;'    => chr( 195 ) . chr( 133 ),
		'&aring;'    => chr( 195 ) . chr( 165 ),
		'&asymp;'    => chr( 226 ) . chr( 137 ) . chr( 136 ),
		'&Atilde;'   => chr( 195 ) . chr( 131 ),
		'&atilde;'   => chr( 195 ) . chr( 163 ),
		'&Auml;'     => chr( 195 ) . chr( 132 ),
		'&auml;'     => chr( 195 ) . chr( 164 ),
		'&bdquo;'    => chr( 226 ) . chr( 128 ) . chr( 158 ),
		'&Beta;'     => chr( 206 ) . chr( 146 ),
		'&beta;'     => chr( 206 ) . chr( 178 ),
		'&brvbar;'   => chr( 194 ) . chr( 166 ),
		'&bull;'     => chr( 226 ) . chr( 128 ) . chr( 162 ),
		'&cap;'      => chr( 226 ) . chr( 136 ) . chr( 169 ),
		'&Ccedil;'   => chr( 195 ) . chr( 135 ),
		'&ccedil;'   => chr( 195 ) . chr( 167 ),
		'&cedil;'    => chr( 194 ) . chr( 184 ),
		'&cent;'     => chr( 194 ) . chr( 162 ),
		'&Chi;'      => chr( 206 ) . chr( 167 ),
		'&chi;'      => chr( 207 ) . chr( 135 ),
		'&circ;'     => chr( 203 ) . chr( 134 ),
		'&clubs;'    => chr( 226 ) . chr( 153 ) . chr( 163 ),
		'&cong;'     => chr( 226 ) . chr( 137 ) . chr( 133 ),
		'&copy;'     => chr( 194 ) . chr( 169 ),
		'&crarr;'    => chr( 226 ) . chr( 134 ) . chr( 181 ),
		'&cup;'      => chr( 226 ) . chr( 136 ) . chr( 170 ),
		'&curren;'   => chr( 194 ) . chr( 164 ),
		'&dagger;'   => chr( 226 ) . chr( 128 ) . chr( 160 ),
		'&Dagger;'   => chr( 226 ) . chr( 128 ) . chr( 161 ),
		'&darr;'     => chr( 226 ) . chr( 134 ) . chr( 147 ),
		'&dArr;'     => chr( 226 ) . chr( 135 ) . chr( 147 ),
		'&deg;'      => chr( 194 ) . chr( 176 ),
		'&Delta;'    => chr( 206 ) . chr( 148 ),
		'&delta;'    => chr( 206 ) . chr( 180 ),
		'&diams;'    => chr( 226 ) . chr( 153 ) . chr( 166 ),
		'&divide;'   => chr( 195 ) . chr( 183 ),
		'&Eacute;'   => chr( 195 ) . chr( 137 ),
		'&eacute;'   => chr( 195 ) . chr( 169 ),
		'&Ecirc;'    => chr( 195 ) . chr( 138 ),
		'&ecirc;'    => chr( 195 ) . chr( 170 ),
		'&Egrave;'   => chr( 195 ) . chr( 136 ),
		'&egrave;'   => chr( 195 ) . chr( 168 ),
		'&empty;'    => chr( 226 ) . chr( 136 ) . chr( 133 ),
		'&emsp;'     => chr( 226 ) . chr( 128 ) . chr( 131 ),
		'&ensp;'     => chr( 226 ) . chr( 128 ) . chr( 130 ),
		'&Epsilon;'  => chr( 206 ) . chr( 149 ),
		'&epsilon;'  => chr( 206 ) . chr( 181 ),
		'&equiv;'    => chr( 226 ) . chr( 137 ) . chr( 161 ),
		'&Eta;'      => chr( 206 ) . chr( 151 ),
		'&eta;'      => chr( 206 ) . chr( 183 ),
		'&ETH;'      => chr( 195 ) . chr( 144 ),
		'&eth;'      => chr( 195 ) . chr( 176 ),
		'&Euml;'     => chr( 195 ) . chr( 139 ),
		'&euml;'     => chr( 195 ) . chr( 171 ),
		'&euro;'     => chr( 226 ) . chr( 130 ) . chr( 172 ),
		'&exist;'    => chr( 226 ) . chr( 136 ) . chr( 131 ),
		'&fnof;'     => chr( 198 ) . chr( 146 ),
		'&forall;'   => chr( 226 ) . chr( 136 ) . chr( 128 ),
		'&frac12;'   => chr( 194 ) . chr( 189 ),
		'&frac14;'   => chr( 194 ) . chr( 188 ),
		'&frac34;'   => chr( 194 ) . chr( 190 ),
		'&frasl;'    => chr( 226 ) . chr( 129 ) . chr( 132 ),
		'&Gamma;'    => chr( 206 ) . chr( 147 ),
		'&gamma;'    => chr( 206 ) . chr( 179 ),
		'&ge;'       => chr( 226 ) . chr( 137 ) . chr( 165 ),
		'&harr;'     => chr( 226 ) . chr( 134 ) . chr( 148 ),
		'&hArr;'     => chr( 226 ) . chr( 135 ) . chr( 148 ),
		'&hearts;'   => chr( 226 ) . chr( 153 ) . chr( 165 ),
		'&hellip;'   => chr( 226 ) . chr( 128 ) . chr( 166 ),
		'&Iacute;'   => chr( 195 ) . chr( 141 ),
		'&iacute;'   => chr( 195 ) . chr( 173 ),
		'&Icirc;'    => chr( 195 ) . chr( 142 ),
		'&icirc;'    => chr( 195 ) . chr( 174 ),
		'&iexcl;'    => chr( 194 ) . chr( 161 ),
		'&Igrave;'   => chr( 195 ) . chr( 140 ),
		'&igrave;'   => chr( 195 ) . chr( 172 ),
		'&image;'    => chr( 226 ) . chr( 132 ) . chr( 145 ),
		'&infin;'    => chr( 226 ) . chr( 136 ) . chr( 158 ),
		'&int;'      => chr( 226 ) . chr( 136 ) . chr( 171 ),
		'&Iota;'     => chr( 206 ) . chr( 153 ),
		'&iota;'     => chr( 206 ) . chr( 185 ),
		'&iquest;'   => chr( 194 ) . chr( 191 ),
		'&isin;'     => chr( 226 ) . chr( 136 ) . chr( 136 ),
		'&Iuml;'     => chr( 195 ) . chr( 143 ),
		'&iuml;'     => chr( 195 ) . chr( 175 ),
		'&Kappa;'    => chr( 206 ) . chr( 154 ),
		'&kappa;'    => chr( 206 ) . chr( 186 ),
		'&Lambda;'   => chr( 206 ) . chr( 155 ),
		'&lambda;'   => chr( 206 ) . chr( 187 ),
		'&lang;'     => chr( 226 ) . chr( 140 ) . chr( 169 ),
		'&laquo;'    => chr( 194 ) . chr( 171 ),
		'&larr;'     => chr( 226 ) . chr( 134 ) . chr( 144 ),
		'&lArr;'     => chr( 226 ) . chr( 135 ) . chr( 144 ),
		'&lceil;'    => chr( 226 ) . chr( 140 ) . chr( 136 ),
		'&ldquo;'    => chr( 226 ) . chr( 128 ) . chr( 156 ),
		'&le;'       => chr( 226 ) . chr( 137 ) . chr( 164 ),
		'&lfloor;'   => chr( 226 ) . chr( 140 ) . chr( 138 ),
		'&lowast;'   => chr( 226 ) . chr( 136 ) . chr( 151 ),
		'&loz;'      => chr( 226 ) . chr( 151 ) . chr( 138 ),
		'&lrm;'      => chr( 226 ) . chr( 128 ) . chr( 142 ),
		'&lsaquo;'   => chr( 226 ) . chr( 128 ) . chr( 185 ),
		'&lsquo;'    => chr( 226 ) . chr( 128 ) . chr( 152 ),
		'&macr;'     => chr( 194 ) . chr( 175 ),
		'&mdash;'    => chr( 226 ) . chr( 128 ) . chr( 148 ),
		'&micro;'    => chr( 194 ) . chr( 181 ),
		'&middot;'   => chr( 194 ) . chr( 183 ),
		'&minus;'    => chr( 226 ) . chr( 136 ) . chr( 146 ),
		'&Mu;'       => chr( 206 ) . chr( 156 ),
		'&mu;'       => chr( 206 ) . chr( 188 ),
		'&nabla;'    => chr( 226 ) . chr( 136 ) . chr( 135 ),
		'&nbsp;'     => chr( 194 ) . chr( 160 ),
		'&ndash;'    => chr( 226 ) . chr( 128 ) . chr( 147 ),
		'&ne;'       => chr( 226 ) . chr( 137 ) . chr( 160 ),
		'&ni;'       => chr( 226 ) . chr( 136 ) . chr( 139 ),
		'&not;'      => chr( 194 ) . chr( 172 ),
		'&notin;'    => chr( 226 ) . chr( 136 ) . chr( 137 ),
		'&nsub;'     => chr( 226 ) . chr( 138 ) . chr( 132 ),
		'&Ntilde;'   => chr( 195 ) . chr( 145 ),
		'&ntilde;'   => chr( 195 ) . chr( 177 ),
		'&Nu;'       => chr( 206 ) . chr( 157 ),
		'&nu;'       => chr( 206 ) . chr( 189 ),
		'&Oacute;'   => chr( 195 ) . chr( 147 ),
		'&oacute;'   => chr( 195 ) . chr( 179 ),
		'&Ocirc;'    => chr( 195 ) . chr( 148 ),
		'&ocirc;'    => chr( 195 ) . chr( 180 ),
		'&OElig;'    => chr( 197 ) . chr( 146 ),
		'&oelig;'    => chr( 197 ) . chr( 147 ),
		'&Ograve;'   => chr( 195 ) . chr( 146 ),
		'&ograve;'   => chr( 195 ) . chr( 178 ),
		'&oline;'    => chr( 226 ) . chr( 128 ) . chr( 190 ),
		'&Omega;'    => chr( 206 ) . chr( 169 ),
		'&omega;'    => chr( 207 ) . chr( 137 ),
		'&Omicron;'  => chr( 206 ) . chr( 159 ),
		'&omicron;'  => chr( 206 ) . chr( 191 ),
		'&oplus;'    => chr( 226 ) . chr( 138 ) . chr( 149 ),
		'&or;'       => chr( 226 ) . chr( 136 ) . chr( 168 ),
		'&ordf;'     => chr( 194 ) . chr( 170 ),
		'&ordm;'     => chr( 194 ) . chr( 186 ),
		'&Oslash;'   => chr( 195 ) . chr( 152 ),
		'&oslash;'   => chr( 195 ) . chr( 184 ),
		'&Otilde;'   => chr( 195 ) . chr( 149 ),
		'&otilde;'   => chr( 195 ) . chr( 181 ),
		'&otimes;'   => chr( 226 ) . chr( 138 ) . chr( 151 ),
		'&Ouml;'     => chr( 195 ) . chr( 150 ),
		'&ouml;'     => chr( 195 ) . chr( 182 ),
		'&para;'     => chr( 194 ) . chr( 182 ),
		'&part;'     => chr( 226 ) . chr( 136 ) . chr( 130 ),
		'&permil;'   => chr( 226 ) . chr( 128 ) . chr( 176 ),
		'&perp;'     => chr( 226 ) . chr( 138 ) . chr( 165 ),
		'&Phi;'      => chr( 206 ) . chr( 166 ),
		'&phi;'      => chr( 207 ) . chr( 134 ),
		'&Pi;'       => chr( 206 ) . chr( 160 ),
		'&pi;'       => chr( 207 ) . chr( 128 ),
		'&piv;'      => chr( 207 ) . chr( 150 ),
		'&plusmn;'   => chr( 194 ) . chr( 177 ),
		'&pound;'    => chr( 194 ) . chr( 163 ),
		'&prime;'    => chr( 226 ) . chr( 128 ) . chr( 178 ),
		'&Prime;'    => chr( 226 ) . chr( 128 ) . chr( 179 ),
		'&prod;'     => chr( 226 ) . chr( 136 ) . chr( 143 ),
		'&prop;'     => chr( 226 ) . chr( 136 ) . chr( 157 ),
		'&Psi;'      => chr( 206 ) . chr( 168 ),
		'&psi;'      => chr( 207 ) . chr( 136 ),
		'&radic;'    => chr( 226 ) . chr( 136 ) . chr( 154 ),
		'&rang;'     => chr( 226 ) . chr( 140 ) . chr( 170 ),
		'&raquo;'    => chr( 194 ) . chr( 187 ),
		'&rarr;'     => chr( 226 ) . chr( 134 ) . chr( 146 ),
		'&rArr;'     => chr( 226 ) . chr( 135 ) . chr( 146 ),
		'&rceil;'    => chr( 226 ) . chr( 140 ) . chr( 137 ),
		'&rdquo;'    => chr( 226 ) . chr( 128 ) . chr( 157 ),
		'&real;'     => chr( 226 ) . chr( 132 ) . chr( 156 ),
		'&reg;'      => chr( 194 ) . chr( 174 ),
		'&rfloor;'   => chr( 226 ) . chr( 140 ) . chr( 139 ),
		'&Rho;'      => chr( 206 ) . chr( 161 ),
		'&rho;'      => chr( 207 ) . chr( 129 ),
		'&rlm;'      => chr( 226 ) . chr( 128 ) . chr( 143 ),
		'&rsaquo;'   => chr( 226 ) . chr( 128 ) . chr( 186 ),
		'&rsquo;'    => chr( 226 ) . chr( 128 ) . chr( 153 ),
		'&sbquo;'    => chr( 226 ) . chr( 128 ) . chr( 154 ),
		'&Scaron;'   => chr( 197 ) . chr( 160 ),
		'&scaron;'   => chr( 197 ) . chr( 161 ),
		'&sdot;'     => chr( 226 ) . chr( 139 ) . chr( 133 ),
		'&sect;'     => chr( 194 ) . chr( 167 ),
		'&shy;'      => chr( 194 ) . chr( 173 ),
		'&Sigma;'    => chr( 206 ) . chr( 163 ),
		'&sigma;'    => chr( 207 ) . chr( 131 ),
		'&sigmaf;'   => chr( 207 ) . chr( 130 ),
		'&sim;'      => chr( 226 ) . chr( 136 ) . chr( 188 ),
		'&spades;'   => chr( 226 ) . chr( 153 ) . chr( 160 ),
		'&sub;'      => chr( 226 ) . chr( 138 ) . chr( 130 ),
		'&sube;'     => chr( 226 ) . chr( 138 ) . chr( 134 ),
		'&sum;'      => chr( 226 ) . chr( 136 ) . chr( 145 ),
		'&sup1;'     => chr( 194 ) . chr( 185 ),
		'&sup2;'     => chr( 194 ) . chr( 178 ),
		'&sup3;'     => chr( 194 ) . chr( 179 ),
		'&sup;'      => chr( 226 ) . chr( 138 ) . chr( 131 ),
		'&supe;'     => chr( 226 ) . chr( 138 ) . chr( 135 ),
		'&szlig;'    => chr( 195 ) . chr( 159 ),
		'&Tau;'      => chr( 206 ) . chr( 164 ),
		'&tau;'      => chr( 207 ) . chr( 132 ),
		'&there4;'   => chr( 226 ) . chr( 136 ) . chr( 180 ),
		'&Theta;'    => chr( 206 ) . chr( 152 ),
		'&theta;'    => chr( 206 ) . chr( 184 ),
		'&thetasym;' => chr( 207 ) . chr( 145 ),
		'&thinsp;'   => chr( 226 ) . chr( 128 ) . chr( 137 ),
		'&THORN;'    => chr( 195 ) . chr( 158 ),
		'&thorn;'    => chr( 195 ) . chr( 190 ),
		'&tilde;'    => chr( 203 ) . chr( 156 ),
		'&times;'    => chr( 195 ) . chr( 151 ),
		'&trade;'    => chr( 226 ) . chr( 132 ) . chr( 162 ),
		'&Uacute;'   => chr( 195 ) . chr( 154 ),
		'&uacute;'   => chr( 195 ) . chr( 186 ),
		'&uarr;'     => chr( 226 ) . chr( 134 ) . chr( 145 ),
		'&uArr;'     => chr( 226 ) . chr( 135 ) . chr( 145 ),
		'&Ucirc;'    => chr( 195 ) . chr( 155 ),
		'&ucirc;'    => chr( 195 ) . chr( 187 ),
		'&Ugrave;'   => chr( 195 ) . chr( 153 ),
		'&ugrave;'   => chr( 195 ) . chr( 185 ),
		'&uml;'      => chr( 194 ) . chr( 168 ),
		'&upsih;'    => chr( 207 ) . chr( 146 ),
		'&Upsilon;'  => chr( 206 ) . chr( 165 ),
		'&upsilon;'  => chr( 207 ) . chr( 133 ),
		'&Uuml;'     => chr( 195 ) . chr( 156 ),
		'&uuml;'     => chr( 195 ) . chr( 188 ),
		'&weierp;'   => chr( 226 ) . chr( 132 ) . chr( 152 ),
		'&Xi;'       => chr( 206 ) . chr( 158 ),
		'&xi;'       => chr( 206 ) . chr( 190 ),
		'&Yacute;'   => chr( 195 ) . chr( 157 ),
		'&yacute;'   => chr( 195 ) . chr( 189 ),
		'&yen;'      => chr( 194 ) . chr( 165 ),
		'&yuml;'     => chr( 195 ) . chr( 191 ),
		'&Yuml;'     => chr( 197 ) . chr( 184 ),
		'&Zeta;'     => chr( 206 ) . chr( 150 ),
		'&zeta;'     => chr( 206 ) . chr( 182 ),
		'&zwj;'      => chr( 226 ) . chr( 128 ) . chr( 141 ),
		'&zwnj;'     => chr( 226 ) . chr( 128 ) . chr( 140 ),
		'&gt;'       => '>',
		'&lt;'       => '<',
	];

	$return = strtr( $str, $htmlentities );
	$return = preg_replace( '/~&#x([0-9a-f]+);i/', 'RSSImport_code_to_utf8( hexdec( "\\1" ) )', $return );
	$return = preg_replace( '/~&#(\d+);/', 'RSSImport_code_to_utf8( \\1 )', $return );

	return $return;
}

// check class wp_widget exists
if ( class_exists( 'WP_Widget' ) ) {
	/**
	 * Class RSSImport_Widget
	 */
	class RSSImport_Widget extends WP_Widget {

		/**
		 * RSSImport_Widget constructor.
		 */
		public function __construct() {
			$widget_ops = [
				'classname'   => 'rssimport',
				'description' => __( 'Entries from any RSS or Atom feed', FB_RSSI_TEXTDOMAIN ),
			];
			parent::__construct( 'rssimport', __( 'RSSImport' ), $widget_ops );
		}

		/**
		 * @param array $args
		 * @param array $instance
		 */
		public function widget( $args, $instance ) {
			$title               = empty( $instance['title'] )
				? '&nbsp;'
				: apply_filters( 'widget_title', $instance['title'] );
			$titlelink           = empty( $instance['titlelink'] )
				? ''
				: $instance['titlelink'];
			$display             = empty( $instance['display'] )
				? '5'
				: $instance['display'];
			$feedurl             = empty( $instance['feedurl'] )
				? 'http://bueltge.de/feed/'
				: $instance['feedurl'];
			$before_desc         = empty( $instance['before_desc'] )
				? ''
				: $instance['before_desc'];
			$displaydescriptions = empty( $instance['displaydescriptions'] )
				? '0'
				: $instance['displaydescriptions'];
			$after_desc          = empty( $instance['after_desc'] )
				? ''
				: $instance['after_desc'];
			$html                = empty( $instance['html'] )
				? '0'
				: $instance['html'];
			$truncatedescchar    = empty( $instance['truncatedescchar'] )
				? '200'
				: $instance['truncatedescchar'];
			$truncatedescstring  = empty( $instance['truncatedescstring'] )
				? ''
				: $instance['truncatedescstring'];
			$truncatetitlechar   = empty( $instance['truncatetitlechar'] )
				? ''
				: $instance['truncatetitlechar'];
			$truncatetitlestring = empty( $instance['truncatetitlestring'] )
				? ' ... '
				: $instance['truncatetitlestring'];
			$before_date         = empty( $instance['before_date'] )
				? ' <small>'
				: $instance['before_date'];
			$date                = empty( $instance['date'] )
				? '0'
				: $instance['date'];
			$after_date          = empty( $instance['after_date'] )
				? '</small>'
				: $instance['after_date'];
			$date_format         = empty( $instance['date_format'] )
				? ''
				: $instance['date_format'];
			$before_creator      = empty( $instance['before_creator'] )
				? ' <small>'
				: $instance['before_creator'];
			$creator             = empty( $instance['creator'] )
				? '0'
				: $instance['creator'];
			$after_creator       = empty( $instance['after_creator'] )
				? '</small>'
				: $instance['after_creator'];
			$start_items         = empty( $instance['start_items'] )
				? '<ul>'
				: $instance['start_items'];
			$end_items           = empty( $instance['end_items'] )
				? '</ul>'
				: $instance['end_items'];
			$start_item          = empty( $instance['start_item'] )
				? '<li>'
				: $instance['start_item'];
			$end_item            = empty( $instance['end_item'] )
				? '</li>'
				: $instance['end_item'];
			$target              = empty( $instance['target'] )
				? ''
				: $instance['target'];
			$rel                 = empty( $instance['rel'] )
				? ''
				: $instance['rel'];
			$desc4title          = empty( $instance['desc4title'] )
				? '0'
				: $instance['desc4title'];
			$charsetscan         = empty( $instance['charsetscan'] )
				? '0'
				: $instance['charsetscan'];
			$debug               = empty( $instance['debug'] )
				? '0'
				: $instance['debug'];
			$before_noitems      = empty( $instance['before_noitems'] )
				? '<p>'
				: $instance['before_noitems'];
			$noitems             = empty( $instance['noitems'] )
				? __( 'No items, feed is empty.',
					FB_RSSI_TEXTDOMAIN )
				: $instance['noitems'];
			$after_noitems       = empty( $instance['after_noitems'] )
				? '</p>'
				: $instance['after_noitems'];
			$before_error        = empty( $instance['before_error'] )
				? '<p>'
				: $instance['before_error'];
			$error               = empty( $instance['error'] )
				? __( 'Error: Feed has an error or is not valid',
					FB_RSSI_TEXTDOMAIN )
				: $instance['error'];
			$after_error         = empty( $instance['after_error'] )
				? '</p>'
				: $instance['after_error'];
			$paging              = empty( $instance['paging'] )
				? '0'
				: $instance['paging'];
			$prev_paging_link    = empty( $instance['prev_paging_link'] )
				? __( '&laquo; Previous',
					FB_RSSI_TEXTDOMAIN )
				: $instance['prev_paging_link'];
			$next_paging_link    = empty( $instance['next_paging_link'] )
				? __( 'Next &raquo;', FB_RSSI_TEXTDOMAIN )
				: $instance['next_paging_link'];
			$prev_paging_title   = empty( $instance['prev_paging_title'] )
				? __( 'more items', FB_RSSI_TEXTDOMAIN )
				: $instance['prev_paging_title'];
			$next_paging_title   = empty( $instance['next_paging_title'] )
				? __( 'more items', FB_RSSI_TEXTDOMAIN )
				: $instance['next_paging_title'];
			$use_simplepie       = $instance['use_simplepie'];
			$view                = $instance['view'];
			$random_sort         = $instance['random_sort'];
			$order               = $instance['order'];

			echo $args['before_widget'];
			if ( '' !== $titlelink ) {
				$title = '<a href="' . $titlelink . '">' . $title . '</a>';
			}
			echo $args['before_title'] . $title . $args['after_title'];
			RSSImport(
				$display,
				$feedurl,
				$before_desc,
				$displaydescriptions,
				$after_desc,
				$html,
				$truncatedescchar,
				$truncatedescstring,
				$truncatetitlechar,
				$truncatetitlestring,
				$before_date,
				$date,
				$after_date,
				$date_format,
				$before_creator,
				$creator,
				$after_creator,
				$start_items,
				$end_items,
				$start_item,
				$end_item,
				$target,
				$rel,
				$desc4title,
				$charsetscan,
				$debug,
				$before_noitems,
				$noitems,
				$after_noitems,
				$before_error,
				$error,
				$after_error,
				$paging,
				$prev_paging_link,
				$next_paging_link,
				$prev_paging_title,
				$next_paging_title,
				$use_simplepie,
				$view,
				$random_sort,
				$order
			);
			echo $args['after_widget'];
		}

		/**
		 * @param array $new_instance
		 * @param array $old_instance
		 *
		 * @return array|string
		 */
		public function update( $new_instance, $old_instance ) {
			$instance['instance']            = $old_instance;
			$instance['title']               = strip_tags( $new_instance['title'] );
			$instance['titlelink']           = esc_url( $new_instance['titlelink'] );
			$instance['display']             = (int) $new_instance['display'];
			$instance['feedurl']             = $new_instance['feedurl'];
			$instance['before_desc']         = $new_instance['before_desc'];
			$instance['displaydescriptions'] = (int) $new_instance['displaydescriptions'];
			$instance['after_desc']          = stripslashes_deep( $new_instance['after_desc'] );
			$instance['html']                = (int) $new_instance['html'];
			$instance['truncatedescchar']    = (int) $new_instance['truncatedescchar'];
			$instance['truncatedescstring']  = $new_instance['truncatedescstring'];
			$instance['truncatetitlechar']   = (int) $new_instance['truncatetitlechar'];
			$instance['truncatetitlestring'] = $new_instance['truncatetitlestring'];
			$instance['before_date']         = $new_instance['before_date'];
			$instance['date']                = (int) $new_instance['date'];
			$instance['after_date']          = $new_instance['after_date'];
			$instance['date_format']         = $new_instance['date_format'];
			$instance['before_creator']      = $new_instance['before_creator'];
			$instance['creator']             = (int) $new_instance['creator'];
			$instance['after_creator']       = $new_instance['after_creator'];
			$instance['start_items']         = $new_instance['start_items'];
			$instance['end_items']           = $new_instance['end_items'];
			$instance['start_item']          = $new_instance['start_item'];
			$instance['end_item']            = $new_instance['end_item'];
			$instance['target']              = $new_instance['target'];
			$instance['rel']                 = $new_instance['rel'];
			$instance['desc4title']          = (int) $new_instance['desc4title'];
			$instance['charsetscan']         = (int) $new_instance['charsetscan'];
			$instance['debug']               = (int) $new_instance['debug'];
			$instance['view']                = (int) $new_instance['view'];
			$instance['before_noitems']      = $new_instance['before_noitems'];
			$instance['noitems']             = $new_instance['noitems'];
			$instance['after_noitems']       = $new_instance['after_noitems'];
			$instance['before_error']        = $new_instance['before_error'];
			$instance['error']               = $new_instance['error'];
			$instance['after_error']         = $new_instance['after_error'];
			$instance['paging']              = (int) $new_instance['paging'];
			$instance['prev_paging_link']    = $new_instance['prev_paging_link'];
			$instance['next_paging_link']    = $new_instance['next_paging_link'];
			$instance['prev_paging_title']   = $new_instance['prev_paging_title'];
			$instance['next_paging_title']   = $new_instance['next_paging_title'];
			$instance['use_simplepie']       = (int) $new_instance['use_simplepie'];
			$instance['random_sort']         = (int) $new_instance['random_sort'];
			$instance['order']               = $new_instance['order'];

			if ( current_user_can( 'unfiltered_html' ) ) {
				return $instance;
			}

			return stripslashes( strip_tags( $instance ) );
		}

		/**
		 * @param array $instance
		 *
		 * @return string|void
		 */
		public function form( $instance ) {
			$instance = wp_parse_args(
				(array) $instance,
				[
					'title'               => '',
					'titlelink'           => '',
					'display'             => 5,
					'feedurl'             => 'http://bueltge.de/feed/',
					'before_desc'         => '',
					'displaydescriptions' => 0,
					'after_desc'          => '',
					'html'                => 0,
					'truncatedescchar'    => 200,
					'truncatedescstring'  => ' ... ',
					'truncatetitlechar'   => '',
					'truncatetitlestring' => ' ... ',
					'before_date'         => ' <small>',
					'date'                => 0,
					'after_date'          => '</small>',
					'date_format'         => '',
					'before_creator'      => ' <small>',
					'creator'             => 0,
					'after_creator'       => '</small>',
					'start_items'         => '<ul>',
					'end_items'           => '</ul>',
					'start_item'          => '<li>',
					'end_item'            => '</li>',
					'target'              => '',
					'rel'                 => '',
					'desc4title'          => 0,
					'charsetscan'         => 0,
					'debug'               => 0,
					'view'                => 1,
					'before_noitems'      => '<p>',
					'noitems'             => __( 'No items, feed is empty.', FB_RSSI_TEXTDOMAIN ),
					'after_noitems'       => '</p>',
					'before_error'        => '<p>',
					'error'               => __( 'Error: Feed has an error or is not valid',
						FB_RSSI_TEXTDOMAIN ),
					'after_error'         => '</p>',
					'paging'              => 0,
					'prev_paging_link'    => __( '&laquo; Previous', FB_RSSI_TEXTDOMAIN ),
					'next_paging_link'    => __( 'Next &raquo;', FB_RSSI_TEXTDOMAIN ),
					'prev_paging_title'   => __( 'more items', FB_RSSI_TEXTDOMAIN ),
					'next_paging_title'   => __( 'more items', FB_RSSI_TEXTDOMAIN ),
					'use_simplepie'       => 1,
					'random_sort'         => 0,
					'order'               => 'date,title,creator,description',
				]
			);

			$title               = format_to_edit( strip_tags( $instance['title'] ) );
			$titlelink           = format_to_edit( esc_url( $instance['titlelink'] ) );
			$display             = (int) $instance['display'];
			$feedurl             = format_to_edit( $instance['feedurl'] );
			$before_desc         = format_to_edit( $instance['before_desc'] );
			$displaydescriptions = (int) $instance['displaydescriptions'];
			$after_desc          = format_to_edit( $instance['after_desc'] );
			$html                = (int) $instance['html'];
			$truncatedescchar    = (int) $instance['truncatedescchar'];
			$truncatedescstring  = (int) $instance['truncatedescstring'];
			$truncatetitlechar   = format_to_edit( $instance['truncatetitlechar'] );
			$truncatetitlestring = format_to_edit( $instance['truncatetitlestring'] );
			$before_date         = format_to_edit( $instance['before_date'] );
			$date                = (int) $instance['date'];
			$after_date          = format_to_edit( $instance['after_date'] );
			$date_format         = format_to_edit( $instance['date_format'] );
			$before_creator      = format_to_edit( $instance['before_creator'] );
			$creator             = (int) $instance['creator'];
			$after_creator       = format_to_edit( $instance['after_creator'] );
			$start_items         = format_to_edit( $instance['start_items'] );
			$end_items           = format_to_edit( $instance['end_items'] );
			$start_item          = format_to_edit( $instance['start_item'] );
			$end_item            = format_to_edit( $instance['end_item'] );
			$target              = format_to_edit( $instance['target'] );
			$rel                 = format_to_edit( $instance['rel'] );
			$desc4title          = (int) $instance['desc4title'];
			$charsetscan         = (int) $instance['charsetscan'];
			$debug               = (int) $instance['debug'];
			$before_noitems      = format_to_edit( $instance['before_noitems'] );
			$noitems             = format_to_edit( $instance['noitems'] );
			$after_noitems       = format_to_edit( $instance['after_noitems'] );
			$before_error        = format_to_edit( $instance['before_error'] );
			$error               = format_to_edit( $instance['error'] );
			$after_error         = format_to_edit( $instance['after_error'] );
			$paging              = (int) $instance['paging'];
			$prev_paging_link    = format_to_edit( $instance['prev_paging_link'] );
			$next_paging_link    = format_to_edit( $instance['next_paging_link'] );
			$prev_paging_title   = format_to_edit( $instance['prev_paging_title'] );
			$next_paging_title   = format_to_edit( $instance['next_paging_title'] );
			$use_simplepie       = (int) $instance['use_simplepie'];
			$view                = (int) $instance['view'];
			$random_sort         = (int) $instance['random_sort'];
			$order               = format_to_edit( $instance['order'] );
			?>
            <p>
                <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat"
                        id="<?php echo $this->get_field_id( 'title' ); ?>"
                        name="<?php echo $this->get_field_name( 'title' ); ?>"
                        type="text"
                        value="<?php echo esc_attr( $title ); ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'titlelink' ); ?>"><?php _e( 'URL for Title (incl. http://):',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat"
                        id="<?php echo $this->get_field_id( 'titlelink' ); ?>"
                        name="<?php echo $this->get_field_name( 'titlelink' ); ?>"
                        type="text"
                        value="<?php echo esc_url( $titlelink ); ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'display' ); ?>"><?php _e( 'Display:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat"
                        id="<?php echo $this->get_field_id( 'display' ); ?>"
                        name="<?php echo $this->get_field_name( 'display' ); ?>"
                        type="text"
                        value="<?php echo esc_attr( $display ); ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'feedurl' ); ?>"><?php _e( 'FeedURL:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat"
                        id="<?php echo $this->get_field_id( 'feedurl' ); ?>"
                        name="<?php echo $this->get_field_name( 'feedurl' ); ?>"
                        type="text"
                        value="<?php echo $feedurl; ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'before_desc' ); ?>"><?php _e( 'Before Description:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat"
                        id="<?php echo $this->get_field_id( 'before_desc' ); ?>"
                        name="<?php echo $this->get_field_name( 'before_desc' ); ?>"
                        type="text"
                        value="<?php echo $before_desc; ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'displaydescriptions' ); ?>"><?php _e( 'Display Description:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <select id="<?php echo $this->get_field_id( 'displaydescriptions' ); ?>"
                        name="<?php echo $this->get_field_name( 'displaydescriptions' ); ?>">
                        <option value="0"<?php if ( $displaydescriptions === '0' ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'False', FB_RSSI_TEXTDOMAIN ); ?></option>
                        <option value="1"<?php if ( $displaydescriptions === '1' ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'True', FB_RSSI_TEXTDOMAIN ); ?></option>
                    </select>
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'after_desc' ); ?>"><?php _e( 'After Description:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat code"
                        id="<?php echo $this->get_field_id( 'after_desc' ); ?>"
                        name="<?php echo $this->get_field_name( 'after_desc' ); ?>"
                        type="text"
                        value="<?php echo $after_desc; ?>" /></label>
                <br />
                <small><?php _e( 'You can use the following strings to create custom links:', FB_RSSI_TEXTDOMAIN ); ?>
                    <code>%title%</code>, <code>%href%</code>
                    <br /><?php _e( 'Example:', FB_RSSI_TEXTDOMAIN ); ?>
                    <code>&lt;a href="%href%" target="self" rel="follow"&gt;%title%&lt;/a&gt;</code></small>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'html' ); ?>"><?php _e( 'HTML:', FB_RSSI_TEXTDOMAIN ) ?>
                    <select id="<?php echo $this->get_field_id( 'html' ); ?>"
                        name="<?php echo $this->get_field_name( 'html' ); ?>">
                        <option value="0"<?php if ( ! $html ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'False', FB_RSSI_TEXTDOMAIN ); ?></option>
                        <option value="1"<?php if ( $html ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'True', FB_RSSI_TEXTDOMAIN ); ?></option>
                    </select>
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'truncatedescchar' ); ?>"><?php _e( 'Truncate Description Char:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat"
                        id="<?php echo $this->get_field_id( 'truncatedescchar' ); ?>"
                        name="<?php echo $this->get_field_name( 'truncatedescchar' ); ?>"
                        type="text"
                        value="<?php echo esc_attr( $truncatedescchar ); ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'truncatedescstring' ); ?>"><?php _e( 'Truncate Description String (HTML):',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat code"
                        id="<?php echo $this->get_field_id( 'truncatedescstring' ); ?>"
                        name="<?php echo $this->get_field_name( 'truncatedescstring' ); ?>"
                        type="text"
                        value="<?php echo $truncatedescstring; ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'truncatetitlechar' ); ?>"><?php _e( 'Truncate Title Char:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat"
                        id="<?php echo $this->get_field_id( 'truncatetitlechar' ); ?>"
                        name="<?php echo $this->get_field_name( 'truncatetitlechar' ); ?>"
                        type="text"
                        value="<?php echo esc_attr( $truncatetitlechar ); ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'truncatetitlestring' ); ?>"><?php _e( 'Truncate Title String (HTML):',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat code"
                        id="<?php echo $this->get_field_id( 'truncatetitlestring' ); ?>"
                        name="<?php echo $this->get_field_name( 'truncatetitlestring' ); ?>"
                        type="text"
                        value="<?php echo $truncatetitlestring; ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'before_date' ); ?>"><?php _e( 'Before Date (HTML):',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat code"
                        id="<?php echo $this->get_field_id( 'before_date' ); ?>"
                        name="<?php echo $this->get_field_name( 'before_date' ); ?>"
                        type="text"
                        value="<?php echo $before_date; ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'date' ); ?>"><?php _e( 'Date:', FB_RSSI_TEXTDOMAIN ) ?>
                    <select id="<?php echo $this->get_field_id( 'date' ); ?>"
                        name="<?php echo $this->get_field_name( 'date' ); ?>">
                        <option value="0"<?php if ( ! $date ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'False', FB_RSSI_TEXTDOMAIN ); ?></option>
                        <option value="1"<?php if ( $date ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'True', FB_RSSI_TEXTDOMAIN ); ?></option>
                    </select>
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'after_date' ); ?>"><?php _e( 'After Date (HTML):',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat code"
                        id="<?php echo $this->get_field_id( 'after_date' ); ?>"
                        name="<?php echo $this->get_field_name( 'after_date' ); ?>"
                        type="text"
                        value="<?php echo $after_date; ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'date_format' ); ?>"><?php _e( 'Date Formatting:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat"
                        id="<?php echo $this->get_field_id( 'date_format' ); ?>"
                        name="<?php echo $this->get_field_name( 'date_format' ); ?>"
                        type="text"
                        value="<?php echo $date_format; ?>" /></label>
                <br />
                <small><?php _e( 'Leave empty for use the date format of your WordPress settings.',
						FB_RSSI_TEXTDOMAIN ); ?>
                    <a href="http://codex.wordpress.org/Formatting_Date_and_Time"><?php _e( 'Documentation on date formatting',
							FB_RSSI_TEXTDOMAIN ); ?></a>
                </small>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'before_creator' ); ?>"><?php _e( 'Before Creator (HTML):',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat code"
                        id="<?php echo $this->get_field_id( 'before_creator' ); ?>"
                        name="<?php echo $this->get_field_name( 'before_creator' ); ?>"
                        type="text"
                        value="<?php echo $before_creator; ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'creator' ); ?>"><?php _e( 'Creator:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <select id="<?php echo $this->get_field_id( 'creator' ); ?>"
                        name="<?php echo $this->get_field_name( 'creator' ); ?>">
                        <option value="0"<?php if ( ! $creator ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'False', FB_RSSI_TEXTDOMAIN ); ?></option>
                        <option value="1"<?php if ( $creator ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'True', FB_RSSI_TEXTDOMAIN ); ?></option>
                    </select>
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'after_creator' ); ?>"><?php _e( 'After Creator (HTML):',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat code"
                        id="<?php echo $this->get_field_id( 'after_creator' ); ?>"
                        name="<?php echo $this->get_field_name( 'after_creator' ); ?>"
                        type="text"
                        value="<?php echo $after_creator; ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'start_items' ); ?>"><?php _e( 'Before Items (HTML):',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat code"
                        id="<?php echo $this->get_field_id( 'start_items' ); ?>"
                        name="<?php echo $this->get_field_name( 'start_items' ); ?>"
                        type="text"
                        value="<?php echo $start_items; ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'end_items' ); ?>"><?php _e( 'After Items (HTML):',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat code"
                        id="<?php echo $this->get_field_id( 'end_items' ); ?>"
                        name="<?php echo $this->get_field_name( 'end_items' ); ?>"
                        type="text"
                        value="<?php echo $end_items; ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'start_item' ); ?>"><?php _e( 'Before Item (HTML):',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat code"
                        id="<?php echo $this->get_field_id( 'start_item' ); ?>"
                        name="<?php echo $this->get_field_name( 'start_item' ); ?>"
                        type="text"
                        value="<?php echo $start_item; ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'end_item' ); ?>"><?php _e( 'After Item (HTML):',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat code"
                        id="<?php echo $this->get_field_id( 'end_item' ); ?>"
                        name="<?php echo $this->get_field_name( 'end_item' ); ?>"
                        type="text"
                        value="<?php echo $end_item; ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'target' ); ?>"><?php _e( 'Target Attribut:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat"
                        id="<?php echo $this->get_field_id( 'target' ); ?>"
                        name="<?php echo $this->get_field_name( 'target' ); ?>"
                        type="text"
                        value="<?php echo esc_attr( $target ); ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'rel' ); ?>"><?php _e( 'Rel Attribut:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat"
                        id="<?php echo $this->get_field_id( 'rel' ); ?>"
                        name="<?php echo $this->get_field_name( 'rel' ); ?>"
                        type="text"
                        value="<?php echo esc_attr( $rel ); ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'desc4title' ); ?>"><?php _e( 'Desc4Title:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <select id="<?php echo $this->get_field_id( 'desc4title' ); ?>"
                        name="<?php echo $this->get_field_name( 'desc4title' ); ?>">
                        <option value="0"<?php if ( ! $desc4title ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'False', FB_RSSI_TEXTDOMAIN ); ?></option>
                        <option value="1"<?php if ( $desc4title ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'True', FB_RSSI_TEXTDOMAIN ); ?></option>
                    </select>
                </label>
                <br />
                <small><?php _e( 'Description for title-Attribut on Title-Link', FB_RSSI_TEXTDOMAIN ); ?></small>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'charsetscan' ); ?>"><?php _e( 'Charsetscan:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <select id="<?php echo $this->get_field_id( 'charsetscan' ); ?>"
                        name="<?php echo $this->get_field_name( 'charsetscan' ); ?>">
                        <option value="0"<?php if ( ! $charsetscan ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'False', FB_RSSI_TEXTDOMAIN ); ?></option>
                        <option value="1"<?php if ( $charsetscan ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'True', FB_RSSI_TEXTDOMAIN ); ?></option>
                    </select>
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'debug' ); ?>"><?php _e( 'Debug mode:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <select id="<?php echo $this->get_field_id( 'debug' ); ?>"
                        name="<?php echo $this->get_field_name( 'debug' ); ?>">
                        <option value="0"<?php if ( ! $debug ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'False', FB_RSSI_TEXTDOMAIN ); ?></option>
                        <option value="1"<?php if ( $debug ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'True', FB_RSSI_TEXTDOMAIN ); ?></option>
                    </select>
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'before_noitems' ); ?>"><?php _e( 'Before <em>No</em> Items Message (HTML):',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat code"
                        id="<?php echo $this->get_field_id( 'before_noitems' ); ?>"
                        name="<?php echo $this->get_field_name( 'before_noitems' ); ?>"
                        type="text"
                        value="<?php echo $before_noitems; ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'noitems' ); ?>"><?php _e( '<em>No</em> Items Message:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat"
                        id="<?php echo $this->get_field_id( 'noitems' ); ?>"
                        name="<?php echo $this->get_field_name( 'noitems' ); ?>"
                        type="text"
                        value="<?php echo esc_attr( $noitems ); ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'after_noitems' ); ?>"><?php _e( 'After <em>No</em> Items Message (HTML):',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat code"
                        id="<?php echo $this->get_field_id( 'after_noitems' ); ?>"
                        name="<?php echo $this->get_field_name( 'after_noitems' ); ?>"
                        type="text"
                        value="<?php echo $after_noitems; ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'before_error' ); ?>"><?php _e( 'Before Error Message (HTML):',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat code"
                        id="<?php echo $this->get_field_id( 'before_error' ); ?>"
                        name="<?php echo $this->get_field_name( 'before_error' ); ?>"
                        type="text"
                        value="<?php echo $before_error; ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'error' ); ?>"><?php _e( 'Error Message:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat"
                        id="<?php echo $this->get_field_id( 'error' ); ?>"
                        name="<?php echo $this->get_field_name( 'error' ); ?>"
                        type="text"
                        value="<?php echo esc_attr( $error ); ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'after_error' ); ?>"><?php _e( 'After Error Message (HTML):',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat code"
                        id="<?php echo $this->get_field_id( 'after_error' ); ?>"
                        name="<?php echo $this->get_field_name( 'after_error' ); ?>"
                        type="text"
                        value="<?php echo $after_error; ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'paging' ); ?>"><?php _e( 'Pagination:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <select id="<?php echo $this->get_field_id( 'paging' ); ?>"
                        name="<?php echo $this->get_field_name( 'paging' ); ?>">
                        <option value="0"<?php if ( ! $paging ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'False', FB_RSSI_TEXTDOMAIN ); ?></option>
                        <option value="1"<?php if ( $paging ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'True', FB_RSSI_TEXTDOMAIN ); ?></option>
                    </select>
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'prev_paging_link' ); ?>"><?php _e( 'Previous Pagination Link String:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat"
                        id="<?php echo $this->get_field_id( 'prev_paging_link' ); ?>"
                        name="<?php echo $this->get_field_name( 'prev_paging_link' ); ?>"
                        type="text"
                        value="<?php echo esc_attr( $prev_paging_link ); ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'next_paging_link' ); ?>"><?php _e( 'Next Pagination Link String:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat"
                        id="<?php echo $this->get_field_id( 'next_paging_link' ); ?>"
                        name="<?php echo $this->get_field_name( 'next_paging_link' ); ?>"
                        type="text"
                        value="<?php echo esc_attr( $next_paging_link ); ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'prev_paging_title' ); ?>"><?php _e( 'Previous Pagination Title String:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat"
                        id="<?php echo $this->get_field_id( 'prev_paging_title' ); ?>"
                        name="<?php echo $this->get_field_name( 'prev_paging_title' ); ?>"
                        type="text"
                        value="<?php echo esc_attr( $prev_paging_title ); ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'next_paging_title' ); ?>"><?php _e( 'Next Pagination Title String:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat"
                        id="<?php echo $this->get_field_id( 'next_paging_title' ); ?>"
                        name="<?php echo $this->get_field_name( 'next_paging_title' ); ?>"
                        type="text"
                        value="<?php echo esc_attr( $next_paging_title ); ?>" /></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'use_simplepie' ); ?>"><?php _e( 'Use SimplePie class:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <select id="<?php echo $this->get_field_id( 'use_simplepie' ); ?>"
                        name="<?php echo $this->get_field_name( 'use_simplepie' ); ?>">
                        <option value="0"<?php if ( ! $use_simplepie ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'False', FB_RSSI_TEXTDOMAIN ); ?></option>
                        <option value="1"<?php if ( $use_simplepie ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'True', FB_RSSI_TEXTDOMAIN ); ?></option>
                    </select>
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'view' ); ?>"><?php _e( 'Echo/Return:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <select id="<?php echo $this->get_field_id( 'view' ); ?>"
                        name="<?php echo $this->get_field_name( 'view' ); ?>">
                        <option value="0"<?php if ( ! $view ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'False', FB_RSSI_TEXTDOMAIN ); ?></option>
                        <option value="1"<?php if ( $view ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'True', FB_RSSI_TEXTDOMAIN ); ?></option>
                    </select>
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'random_sort' ); ?>"><?php _e( 'Random Order:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <select id="<?php echo $this->get_field_id( 'random_sort' ); ?>"
                        name="<?php echo $this->get_field_name( 'random_sort' ); ?>">
                        <option value="0"<?php if ( ! $random_sort ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'False', FB_RSSI_TEXTDOMAIN ); ?></option>
                        <option value="1"<?php if ( $random_sort ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'True', FB_RSSI_TEXTDOMAIN ); ?></option>
                    </select>
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'order' ); ?>"><?php _e( 'Order for Date, Title, Creator, Description:',
						FB_RSSI_TEXTDOMAIN ) ?>
                    <input class="widefat code"
                        id="<?php echo $this->get_field_id( 'order' ); ?>"
                        name="<?php echo $this->get_field_name( 'order' ); ?>"
                        type="text"
                        value="<?php echo $order; ?>" /></label>
                <br />
                <small><?php _e( 'Use a comma separated string for your order. On default, it is',
						FB_RSSI_TEXTDOMAIN ); ?> <code>date,title,creator,description</code></small>
            </p>
			<?php
		}
	}

	add_action( 'widgets_init', function() { register_widget("RSSImport_Widget"); } );
} // end if class wp_widget exists
