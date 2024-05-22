<?php
/**
 * Class that renders OAI-PMH 2.0 output.
 *
 * @package vb-metadata-export
 */

/**
 * Class imports.
 */
require_once plugin_dir_path( __FILE__ ) . '/class-vb-metadata-export-common.php';
require_once plugin_dir_path( __FILE__ ) . '/class-vb-metadata-export-marc21xml.php';
require_once plugin_dir_path( __FILE__ ) . '/class-vb-metadata-export-converter.php';
require_once plugin_dir_path( __FILE__ ) . '/class-vb-metadata-export-dc.php';

if ( ! class_exists( 'VB_Metadata_Export_OAI_PMH' ) ) {

	/**
	 * Class that renders OAI-PMH 2.0 output.
	 */
	class VB_Metadata_Export_OAI_PMH {
		/**
		 * Common methods
		 *
		 * @var VB_Metadata_Export_Common
		 */
		protected $common;

		/**
		 * Initialize class with plugin name.
		 *
		 * @param string $plugin_name the name of this plugin.
		 */
		public function __construct( $plugin_name ) {
			$this->common = new VB_Metadata_Export_Common( $plugin_name );
		}

		/**
		 * Escape function that is used throughout OAI-PMH rendering.
		 *
		 * @param string $str the string that is escaped.
		 */
		protected function escape( $str ) {
			return htmlspecialchars( html_entity_decode( $str ), ENT_XML1, 'UTF-8' );
		}

		/**
		 * Convert a datetime object to an string in ISO-8601 format.
		 *
		 * @param DateTime|DateTimeImmutable $date the datetime object that is converted.
		 * @return string the date as ISO-8601 format
		 */
		protected function date_to_iso8601( $date ) {
			return $date->format( 'Y-m-d\TH:i:s\Z' );
		}

		/**
		 * Convert UTC date provided as string in ISO-8601 format to datetime object.
		 *
		 * @param string $iso the date and time as ISO-8601 string.
		 * @return DateTimeImmutable datetime with UTC timezone
		 */
		protected function iso8601_to_date( $iso ) {
			$date = date_create_immutable_from_format( 'Y-m-d\TH:i:s\Z', $iso, new DateTimeZone( 'UTC' ) );
			if ( ! $date ) {
				$date = date_create_immutable_from_format( 'Y-m-d', $iso, new DateTimeZone( 'UTC' ) );
			}
			return $date;
		}

		/**
		 * Retrieve WordPress post id from a oai-pmh identifier string.
		 *
		 * @param string $identifier the oai-pmh identifier that references a post.
		 * @return int the post id as integer or false, if it could not be found
		 */
		protected function get_post_id_from_identifier( $identifier ) {
			$url  = wp_parse_url( site_url() );
			$path = isset( $url['path'] ) ? $url['path'] : '';
			$host = $url['host'];
			return (int) str_replace( 'oai:' . $host . '/' . $path, '', $identifier ) ?? false;
		}

		/**
		 * Convert a WordPress date in GMT format to a string in ISO-8601 format.
		 *
		 * @param string $post_date the WordPress date in GMT format.
		 * @return string the date as string in ISO-8601 format
		 */
		protected function wordpress_gmt_date_to_iso8601( $post_date ) {
			$date = date_create_immutable_from_format( 'Y-m-d H:i:s', $post_date, new DateTimeZone( 'UTC' ) );
			return $this->date_to_iso8601( $date );
		}

		/**
		 * Return the date of the oldest post based on its modified date as string in ISO-8601 format.
		 *
		 * @return string the modified date of the oldest post in ISO-8601 format
		 */
		protected function get_earliest_post_modified_date() {
			$oldest_posts = get_posts(
				array(
					'numberposts'         => 1,
					'orderby'             => 'modified',
					'order'               => 'ASC',
					'ignore_sticky_posts' => true,
				),
			);
			if ( count( $oldest_posts ) === 1 ) {
				return $this->wordpress_gmt_date_to_iso8601( $oldest_posts[0]->post_date_gmt );
			}
			// no posts yet, return current date.
			return $this->date_to_iso8601( new DateTime() );
		}

		/**
		 * Convert the OAI-PMH metadata prefix constants referencing a particular format to the constants used
		 * by this plugin.
		 *
		 * @param string $metadata_prefix the OAI-PMH metadata prefix format.
		 * @return string the format that is used by this plugin
		 */
		protected function metadata_prefix_to_common_format( $metadata_prefix ) {
			return array(
				'oai_dc'     => 'dc',
				'mods-xml'   => 'mods',
				'MARC21-xml' => 'marc21xml',
			)[ $metadata_prefix ];
		}

		/**
		 * Checks whether a OAI-PMH metadata prefix is supported by this plugin.
		 *
		 * @param string $metadata_prefix the OAI-PMH metadata prefix format.
		 * @return bool true, if it is a supported metadata prefix format
		 */
		protected function is_valid_metadata_prefix( $metadata_prefix ) {
			return in_array( $metadata_prefix, array( 'oai_dc', 'mods-xml', 'MARC21-xml' ), true ) &&
				$this->common->is_format_enabled( $this->metadata_prefix_to_common_format( $metadata_prefix ) );
		}

		/**
		 * Check arguments of a OAI-PMH query that returns a list of results for common errors.
		 *
		 * @param string $verb the OAI-PMH verb of the query.
		 * @param string $metadata_prefix OAI-PMH metadata prefix requesting a specific format.
		 * @param string $from the optional date as ISO-8601 requesting posts older or equal to this date.
		 * @param string $until the optional date as ISO-8601 requesting posts younger or equal to this date.
		 * @param string $resumption_token a token that includes all parameters including an offset for page navigation.
		 * @return string an error message in case some argument is provided incorrectly or false
		 */
		protected function get_list_arguments_error( $verb, $metadata_prefix, $from, $until, $resumption_token ) {
			if ( empty( $metadata_prefix ) ) {
				return $this->render_error( $verb, 'badArgument', 'metadataPrefix is required' );
			}
			if ( ! $this->is_valid_metadata_prefix( $metadata_prefix ) ) {
				return $this->render_error( $verb, 'cannotDisseminateFormat', 'invalid metadataPrefix format' );
			}
			if ( ! empty( $from ) && ! $this->iso8601_to_date( $from ) ) {
				return $this->render_error( $verb, 'badArgument', 'from date is not iso8601' );
			}
			if ( ! empty( $until ) && ! $this->iso8601_to_date( $until ) ) {
				return $this->render_error( $verb, 'badArgument', 'until date is not iso8601' );
			}
			if ( ! empty( $from ) && ! empty( $until ) && strpos( $from, 'T' ) !== strpos( $until, 'T' ) ) {
				return $this->render_error(
					$verb,
					'badArgument',
					'both from and until date should have same granularity'
				);
			}
			return false;
		}

		/**
		 * Return the plugin setting that defines the maximum number of results that are returned in one response.
		 *
		 * @return int the maximum list size setting
		 */
		protected function get_list_size_setting() {
			$list_size = (int) $this->common->get_settings_field_value( 'oai-pmh_list_size' );
			if ( $list_size <= 0 ) {
				return 10;
			}
			return $list_size;
		}

		/**
		 * Combines query arguments such that they can be rendered as html attributes.
		 *
		 * @param array $query query arguments as associaitve array of key and value.
		 * @return string space separated key=value html attribute pairs.
		 */
		protected function implode_query_arguments_as_html_attributes( $query ) {
			$arguments = array();
			foreach ( $query as $key => $value ) {
				array_push( $arguments, esc_html( $key ) . '="' . esc_attr( $value ) . '"' );
			}
			return implode( ' ', $arguments );
		}

		/**
		 * Return rendering of OAI-PMH header.
		 *
		 * @param WP_Post $post the post to be rendered.
		 * @return string the OAI-PMH header xml as string
		 */
		protected function get_post_header( $post ) {
			return implode(
				'',
				array(
					'<header',
					'trash' === $post->post_status ? ' status="deleted"' : '',
					'>',
					'<identifier>',
					$this->escape( $this->get_post_identifier( $post ) ),
					'</identifier>',
					'<datestamp>',
					$this->escape( $this->wordpress_gmt_date_to_iso8601( $post->post_modified_gmt ) ),
					'</datestamp>',
					'<setSpec>posts</setSpec>',
					'</header>',
				),
			);
		}

		/**
		 * Return rendering of post metadata given a specific OAI-PMH metadata prefix.
		 *
		 * @param WP_Post $post the post to be rendered.
		 * @param string  $metadata_prefix the OAI-PMH metadata prefix format.
		 * @return string the rendered xml of the metadata
		 */
		protected function get_post_metadata( $post, $metadata_prefix ) {
			if ( 'trash' === $post->post_status ) {
				// do not show metadata of deleted posts.
				return '';
			}
			$renderer  = new VB_Metadata_Export_Marc21Xml( $this->common->plugin_name );
			$converter = new VB_Metadata_Export_Converter();
			$marc21xml = $renderer->render( $post );
			$metadata  = '';
			if ( 'MARC21-xml' === $metadata_prefix ) {
				$metadata = $marc21xml;
			}
			if ( 'oai_dc' === $metadata_prefix ) {
				$oaidc    = new VB_Metadata_Export_DC( $this->common->plugin_name );
				$metadata = $oaidc->render( $post );
			}
			if ( 'mods-xml' === $metadata_prefix ) {
				$metadata = $converter->convert_marc21_xml_to_mods( $marc21xml );
			}
			$metadata = str_replace( '<?xml version="1.0" encoding="UTF-8"?>', '', $metadata );
			$metadata = str_replace( '<?xml version="1.0"?>', '', $metadata );
			return '<metadata>' . $metadata . '</metadata>';
		}

		/**
		 * Subtract the WordPress timezone offset from the provided date string in ISO-8601 format.
		 *
		 * Is used to counteract the conversion from local to UTC time when a date is provided to WP_Query.
		 *
		 * @param string $utc_iso the date and time as string in ISO-8601 format.
		 * @return string the adjusted date and time in ISO-8601 format
		 */
		protected function subtract_timezone_offset_from_utc_iso8601( $utc_iso ) {
			$utc_date = $this->iso8601_to_date( $utc_iso );
			$date     = new Datetime( 'now', new DateTimeZone( 'UTC' ) );
			$date->setTimestamp( $utc_date->getTimestamp() - wp_timezone()->getOffset( $utc_date ) );
			return $this->date_to_iso8601( $date );
		}

		/**
		 * Checks and adds ISO-8601 time if user only provides input in day granularity.
		 *
		 * @param string $date the date string that is checked and adjusted.
		 * @param bool   $is_after whether to add 00:00:00 (true) or 23:59:59 (false).
		 * @return string the adjusted date string including time
		 */
		protected function complete_day_input( $date, $is_after ) {
			if ( strpos( $date, 'T' ) === false ) {
				if ( $is_after ) {
					return $date . 'T00:00:00Z';
				} else {
					return $date . 'T23:59:59Z';
				}
			}
			return $date;
		}

		/**
		 * Build WP_Query that returns the relevant posts in a certain time interval.
		 *
		 * @param int    $offset the page offset.
		 * @param string $from the beginning of the time interval.
		 * @param string $until the end of the time interval.
		 * @return WP_Query an instance of WP_Query that returns the correct posts
		 */
		protected function query_for_posts( $offset, $from, $until ) {
			$now    = $this->date_to_iso8601( new DateTime( 'now', new DateTimeZone( 'UTC' ) ) );
			$after  = empty( $from ) ? $this->get_earliest_post_modified_date() : $from;
			$before = empty( $until ) ? $now : $until;
			$after  = $this->complete_day_input( $after, true );
			$before = $this->complete_day_input( $before, false );

			// convert after/before to UTC (even though they are UTC) because date_query will always convert to local.
			$after  = $this->subtract_timezone_offset_from_utc_iso8601( $after );
			$before = $this->subtract_timezone_offset_from_utc_iso8601( $before );

			$query_args = array(
				'offset'              => $offset,
				'date_query'          => array(
					'column' => 'post_modified_gmt',
					array(
						'after'     => $after,
						'before'    => $before,
						'inclusive' => true,
					),
				),
				'post_type'           => 'post',
				'post_status'         => array( 'publish', 'trash' ),
				'posts_per_page'      => $this->get_list_size_setting(),
				'orderby'             => 'modified',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
			);

			$require_doi = $this->common->get_settings_field_value( 'require_doi' );
			if ( $require_doi ) {
				$doi_meta_keys            = $this->common->get_settings_field_value( 'doi_meta_key' );
				$doi_meta_keys            = explode( ',', $doi_meta_keys, 2 );
				$doi_meta_keys            = array_filter( array_map( 'trim', $doi_meta_keys ) );
				$query_args['meta_query'] = array( // phpcs:ignore
					'relation' => 'OR',
					array(
						'key'     => $doi_meta_keys[0],
						'value'   => '',
						'compare' => '!=',
					),
				);
				if ( count( $doi_meta_keys ) === 2 ) {
					array_push(
						$query_args['meta_query'],
						array(
							'key'     => $doi_meta_keys[1],
							'value'   => '',
							'compare' => '!=',
						),
					);
				}
			}

			return new WP_Query( $query_args );
		}

		/**
		 * Extract query parameters from a resumption token.
		 *
		 * The resumption token is a base64 encoded string of URL-encoded query arguments.
		 *
		 * @param string $token the resumption token.
		 * @return array an associative array of the options included in the resumption token
		 */
		protected function parse_resumption_token( $token ) {
			if ( empty( $token ) ) {
				return array();
			}
			parse_str( base64_decode( $token ), $options ); // phpcs:ignore
			$required_options  = array( 'offset', 'from', 'until', 'metadataPrefix', 'set' );
			$extracted_optinos = array_intersect_key( $options, array_flip( $required_options ) );
			if ( count( $required_options ) !== count( $extracted_optinos ) ) {
				// there are options missing.
				return array();
			}
			return $extracted_optinos;
		}


		/**
		 * Return a rendered resumption token including all query arguments as xml string.
		 *
		 * @param int    $offset the page offset.
		 * @param int    $list_count the number of posts that are returned for one request (page size).
		 * @param int    $total_count the total number of posts that match the query.
		 * @param string $from the beginning of the date interal as ISO-8601 string.
		 * @param string $until the end of the date interval as ISO-8601 string.
		 * @param string $prefix the OAI-PMH metadata prefix format.
		 * @param string $set a the set of posts that are queried (not implemented).
		 * @return string xml as string of the resumption token
		 */
		protected function build_resumption_token( $offset, $list_count, $total_count, $from, $until, $prefix, $set ) {
			$now         = $this->date_to_iso8601( new DateTime( 'now', new DateTimeZone( 'UTC' ) ) );
			$next_offset = $offset + $list_count;
			$options     = array(
				'offset'         => $next_offset,
				'from'           => empty( $from ) ? $this->get_earliest_post_modified_date() : $from,
				'until'          => empty( $until ) ? $now : $until,
				'metadataPrefix' => $prefix,
				'set'            => empty( $set ) ? 'posts' : $set,
			);
			$token       = http_build_query( $options );
			return implode(
				'',
				array(
					'<resumptionToken ',
					'cursor="' . esc_attr( $offset ) . '" ',
					'completeListSize="' . esc_attr( $total_count ) . '">',
					esc_html( base64_encode( $token ) ), // phpcs:ignore
					'</resumptionToken>',
				),
			);
		}

		/**
		 * Return rendering of a list request (verb=ListRecords or verb=ListIdentifiers).
		 *
		 * @param string $verb the OAI-PMH verb, either ListRecords or ListIdentifiers.
		 * @param string $metadata_prefix the OAI-PMH metadata prefix format.
		 * @param string $from the beginning of the date interal as ISO-8601 string.
		 * @param string $until the end of the date interval as ISO-8601 string.
		 * @param string $set a the set of posts that are queried (not implemented).
		 * @param string $resumption_token the resumption token.
		 * @return string the rendered xml of the list request
		 */
		protected function render_list_request( $verb, $metadata_prefix, $from, $until, $set, $resumption_token ) {
			$request_options = array_filter(
				array(
					'verb'            => $verb,
					'from'            => $from,
					'until'           => $until,
					'metadataPrefix'  => $metadata_prefix,
					'set'             => $set,
					'resumptionToken' => $resumption_token,
				),
			);

			if ( ! empty( $resumption_token ) && ( ! empty( $from ) || ! empty( $until ) ) ) {
				return $this->render_error(
					$verb,
					'badArgument',
					'date not allowed in combination with resumptionToken'
				);
			}

			$resumption_options = $this->parse_resumption_token( $resumption_token );
			if ( ! empty( $resumption_token ) && empty( $resumption_options ) ) {
				return $this->render_error( $verb, 'badResumptionToken', 'invalid resumption token' );
			}

			$offset = 0;
			if ( ! empty( $resumption_options ) ) {
				$offset          = $resumption_options['offset'];
				$from            = $resumption_options['from'];
				$until           = $resumption_options['until'];
				$metadata_prefix = $resumption_options['metadataPrefix'];
				$set             = $resumption_options['set'];
			}

			$error = $this->get_list_arguments_error( $verb, $metadata_prefix, $from, $until, $resumption_token );
			if ( $error ) {
				return $error;
			}

			$query       = $this->query_for_posts( $offset, $from, $until );
			$total_count = $query->found_posts;
			$list_count  = $query->post_count;
			$posts       = $query->get_posts();

			if ( 0 === $list_count ) {
				return $this->render_error( $verb, 'noRecordsMatch', 'no records match the provided criteria' );
			}

			$xml_array = array( '<' . esc_html( $verb ) . '>' );
			foreach ( $posts as $post ) {
				$xml_array = array_merge(
					$xml_array,
					array(
						'ListRecords' === $verb ? '<record>' : '',
						$this->get_post_header( $post ),
						'ListRecords' === $verb ? $this->get_post_metadata( $post, $metadata_prefix ) : '',
						'ListRecords' === $verb ? '</record>' : '',
					),
				);
			}
			if ( $total_count > $list_count + $offset ) {
				// there are more results, use resumptionToken.
				$resumption_xml = $this->build_resumption_token(
					$offset,
					$list_count,
					$total_count,
					$from,
					$until,
					$metadata_prefix,
					$set,
				);
				$xml_array      = array_merge( $xml_array, array( $resumption_xml ) );
			}
			$xml_array = array_merge( $xml_array, array( '</' . esc_html( $verb ) . '>' ) );

			return $this->render_response( $request_options, implode( '', $xml_array ) );
		}

		/**
		 * Return OAI-PMH base URL based on the WordPress home URL.
		 *
		 * @return string the OAI-PMH base URL as string
		 */
		public function get_base_url() {
			return get_home_url() . '/oai/repository/';
		}

		/**
		 * Build the OAI-PMH identifier for a post.
		 *
		 * @param WP_Post $post the post to identify.
		 * @return string a OAI-PMH identifier for the post as string
		 */
		public function get_post_identifier( $post ) {
			$url  = wp_parse_url( site_url() );
			$path = isset( $url['path'] ) ? $url['path'] : '';
			$host = $url['host'];
			return 'oai:' . $host . '/' . $path . $post->ID;
		}

		/**
		 * Return the permalink to the OAI-PMH GetRecord request for a post.
		 *
		 * @param WP_Post $post the post whose permalink is returned.
		 * @return string the permalink to the OAI-PMH GetRecord request
		 */
		public function get_permalink( $post ) {
			return $this->get_base_url() .
				'?verb=GetRecord&metadataPrefix=oai_dc&identifier=' .
				$this->get_post_identifier( $post );
		}

		/**
		 * Render a OAI-PMH request based on the various paramters provided in the request URL (query_var).
		 *
		 * @return string the rendered XML response as string
		 */
		public function render() {
			$verb             = get_query_var( 'verb' );
			$identifier       = get_query_var( 'identifier' );
			$metadata_prefix  = get_query_var( 'metadataPrefix' );
			$from             = get_query_var( 'from' );
			$until            = get_query_var( 'until' );
			$set              = get_query_var( 'set' );
			$resumption_token = get_query_var( 'resumptionToken' );

			if ( 'Identify' === $verb ) {
				return $this->render_identify();
			}
			if ( 'ListSets' === $verb ) {
				return $this->render_list_sets();
			}
			if ( 'ListMetadataFormats' === $verb ) {
				return $this->render_list_metadata_formats();
			}
			if ( 'GetRecord' === $verb ) {
				return $this->render_get_record( $identifier, $metadata_prefix );
			}
			if ( 'ListIdentifiers' === $verb ) {
				return $this->render_list_identifiers( $metadata_prefix, $from, $until, $set, $resumption_token );
			}
			if ( 'ListRecords' === $verb ) {
				return $this->render_list_records( $metadata_prefix, $from, $until, $set, $resumption_token );
			}
			return $this->render_error( $verb, 'badVerb', 'verb argument is not a legal OAI-PMH verb' );
		}

		/**
		 * Combine the content of a response with its outer OAI-PMH xml skeleton.
		 *
		 * @param array  $query query arguments as associative array.
		 * @param string $content the rendered content of the response.
		 * @return string the combination of the OAI-PMH xml skeleton and the content of the response as string
		 */
		public function render_response( $query, $content ) {
			$base_url          = $this->get_base_url();
			$response_date     = $this->date_to_iso8601( new DateTime() );
			$request_arguments = $this->implode_query_arguments_as_html_attributes( $query );
			$xml               = implode(
				'',
				array(
					'<?xml version="1.0" encoding="UTF-8"?>',
					"\n",
					'<OAI-PMH xsi:schemaLocation="',
					'http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd" ',
					'xmlns="http://www.openarchives.org/OAI/2.0/" ',
					'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">',
					"\n",
					'<responseDate>' . $this->escape( $response_date ) . '</responseDate>',
					'<request ' . $request_arguments . '>' . $this->escape( $base_url ) . '</request>',
					$content,
					'</OAI-PMH>',
				),
			);
			return $this->common->format_xml( $xml );
		}

		/**
		 * Return rendered xml containing error message.
		 *
		 * @param string $verb the OAI-PMH verb of the request that has an error.
		 * @param string $code the error code.
		 * @param string $text the error message.
		 * @return string rendered xml of the error
		 */
		public function render_error( $verb, $code, $text ) {
			$xml = "<error code=\"{$code}\">{$text}</error>";
			return $this->render_response( array(), $xml );
		}

		/**
		 * Return rendered xml of OAI-PMH identify request content.
		 *
		 * @return string the rendered xml as string
		 */
		public function render_identify() {
			// Example: https://services.dnb.de/oai/repository?verb=Identify .
			$blog_title    = $this->common->get_settings_field_value( 'blog_title' );
			$base_url      = $this->get_base_url();
			$earliest_date = $this->get_earliest_post_modified_date();
			$admin_email   = $this->common->get_settings_field_value( 'oai-pmh_admin_email' );

			$xml = implode(
				'',
				array(
					'<Identify>',
					'<repositoryName>' . $this->escape( $blog_title ) . '</repositoryName>',
					'<baseURL>' . $this->escape( $base_url ) . '</baseURL>',
					'<protocolVersion>2.0</protocolVersion>',
					'<adminEmail>' . $this->escape( $admin_email ) . '</adminEmail>',
					'<earliestDatestamp>' . $this->escape( $earliest_date ) . '</earliestDatestamp>',
					'<deletedRecord>transient</deletedRecord>',
					'<granularity>YYYY-MM-DDThh:mm:ssZ</granularity>',
					'</Identify>',
				),
			);
			return $this->render_response( array( 'verb' => 'Identify' ), $xml );
		}

		/**
		 * Return rendered xml for a OAI-PMH ListSets request.
		 *
		 * @return string the rendered xml as string
		 */
		public function render_list_sets() {
			// example: https://services.dnb.de/oai/repository?verb=ListSets .
			$xml = implode(
				'',
				array(
					'<ListSets>',
					'<set>',
					'<setSpec>posts</setSpec>',
					'<setName>Posts</setName>',
					'</set>',
					'</ListSets>',
				),
			);
			return $this->render_response( array( 'verb' => 'ListSets' ), $xml );
		}

		/**
		 * Return rendered xml for OAI-PMH ListMetadataFormats request.
		 *
		 * @return string the rendered xml as string
		 */
		public function render_list_metadata_formats() {
			// example: https://services.dnb.de/oai/repository?verb=ListMetadataFormats .
			$xml = implode(
				'',
				array(
					'<ListMetadataFormats>',
					$this->common->is_format_enabled( 'dc' ) ? '<metadataFormat>
						<metadataPrefix>oai_dc</metadataPrefix>
						<schema>http://www.openarchives.org/OAI/2.0/oai_dc.xsd</schema>
						<metadataNamespace>http://www.openarchives.org/OAI/2.0/oai_dc</metadataNamespace>
						</metadataFormat>' : '',
					$this->common->is_format_enabled( 'mods' ) ? '<metadataFormat>
						<metadataPrefix>mods-xml</metadataPrefix>
						<schema>http://www.loc.gov/standards/mods/v3/mods-3-7.xsd</schema>
						<metadataNamespace>http://www.loc.gov/mods/v3</metadataNamespace>
						</metadataFormat>' : '',
					$this->common->is_format_enabled( 'marc21xml' ) ? '<metadataFormat>
						<metadataPrefix>MARC21-xml</metadataPrefix>
						<schema>http://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd</schema>
						<metadataNamespace>http://www.loc.gov/MARC21/slim</metadataNamespace>
						</metadataFormat>' : '',
					'</ListMetadataFormats>',
				),
			);
			return $this->render_response( array( 'verb' => 'ListMetadataFormats' ), $xml );
		}

		/**
		 * Return rendered xml for an OAI-PMH GetRecord request.
		 *
		 * @param string $identifier the OAI-PMH identifier for a post.
		 * @param string $metadata_prefix the OAI-PMH metadata prefix format.
		 * @return string the rendered xml as string
		 */
		public function render_get_record( $identifier, $metadata_prefix ) {
			// example: https://services.dnb.de/oai/repository?verb=GetRecord&metadataPrefix=oai_dc&identifier=oai:dnb.de/dnb/1277033072 .
			if ( empty( $metadata_prefix ) ) {
				return $this->render_error( 'GetRecord', 'badArgument', 'metadataPrefix required' );
			}
			if ( ! $this->is_valid_metadata_prefix( $metadata_prefix ) ) {
				return $this->render_error( 'GetRecord', 'cannotDisseminateFormat', 'invalid metadataPrefix format' );
			}
			if ( empty( $identifier ) ) {
				return $this->render_error( 'GetRecord', 'badArgument', 'identifier required' );
			}
			$post_id = $this->get_post_id_from_identifier( $identifier );
			if ( $post_id <= 0 ) {
				return $this->render_error( 'GetRecord', 'idDoesNotExist', 'invalid identifier' );
			}

			$post = get_post( $post_id );

			if ( ! is_post_publicly_viewable( $post ) && ! 'trash' === $post->post_status ) {
				return $this->render_error( 'GetRecord', 'idDoesNotExist', 'invalid identifier' );
			}

			$require_doi = $this->common->get_settings_field_value( 'require_doi' );
			if ( $require_doi ) {
				$doi = $this->common->get_post_doi( $post );
				if ( empty( $doi ) ) {
					return $this->render_error( 'GetRecord', 'idDoesNotExist', 'invalid identifier' );
				}
			}

			$xml = implode(
				'',
				array(
					'<GetRecord>',
					$this->get_post_header( $post ),
					$this->get_post_metadata( $post, $metadata_prefix ),
					'</GetRecord>',
				),
			);

			return $this->render_response(
				array(
					'verb'           => 'GetRecord',
					'identifier'     => $identifier,
					'metadataPrefix' => $metadata_prefix,
				),
				$xml
			);
		}

		/**
		 * Return rendered xml for a OAI-PMH ListRecords request.
		 *
		 * @param string $prefix the OAI-PMH metadata prefix format.
		 * @param string $from the beginning of the date interal as ISO-8601 string.
		 * @param string $until the end of the date interval as ISO-8601 string.
		 * @param string $set a the set of posts that are queried (not implemented).
		 * @param string $token the resumption token.
		 */
		public function render_list_records( $prefix, $from = null, $until = null, $set = null, $token = null ) {
			return $this->render_list_request( 'ListRecords', $prefix, $from, $until, $set, $token );
		}

		/**
		 * Return rendered xml for a OAI-PMH ListIdentifiers request.
		 *
		 * @param string $prefix the OAI-PMH metadata prefix format.
		 * @param string $from the beginning of the date interal as ISO-8601 string.
		 * @param string $until the end of the date interval as ISO-8601 string.
		 * @param string $set a the set of posts that are queried (not implemented).
		 * @param string $token the resumption token.
		 */
		public function render_list_identifiers( $prefix, $from = null, $until = null, $set = null, $token = null ) {
			// example: https://services.dnb.de/oai/repository?verb=ListIdentifiers&metadataPrefix=oai_dc&from=2023-01-01&until=2023-01-02 .
			return $this->render_list_request( 'ListIdentifiers', $prefix, $from, $until, $set, $token );
		}

		/**
		 * Register rewrite rules with WordPress.
		 */
		public function add_rewrite_rules() {
			// write rules for OAI-PMH.
			add_rewrite_rule( '^oai/repository/?([^/]*)', 'index.php?' . $this->common->plugin_name . '=oai-pmh&$matches[1]', 'top' );
			add_rewrite_tag( '%verb%', '([^&]+)' );
			add_rewrite_tag( '%identifier%', '([^&]+)' );
			add_rewrite_tag( '%metadataPrefix%', '([^&]+)' );
			add_rewrite_tag( '%from%', '([^&]+)' );
			add_rewrite_tag( '%until%', '([^&]+)' );
			add_rewrite_tag( '%resumptionToken%', '([^&]+)' );
			add_rewrite_tag( '%set%', '([^&]+)' );
		}

		/**
		 * WordPress init action hook
		 */
		public function action_init() {
			$this->add_rewrite_rules();
		}

		/**
		 * Run function that is called by main class.
		 */
		public function run() {
			add_action( 'init', array( $this, 'action_init' ) );
		}

	}

}
