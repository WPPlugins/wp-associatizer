<?php
/*
Plugin Name: WP-Associatizer
Plugin URI: http://dancingmammoth.com/wp-associatizer/
Description: This WordPress plugin creates Amazon search links in triple square brackets and reformats Amazon.com product links in posts and comments so they automatically include a specified Amazon Associates Tracking ID.
Author: Dancing Mammoth
Version: 2.9
Author URI: http://dancingmammoth.com/

Changes:

08/22/16: Version 2.9

	Confirming it's stable with 4.6.

06/23/16: Version 2.8

	Fixing Stable Tag declaration.

06/21/16: Version 2.7

	Fix to logic for Giveback functionality to make it behave as intended under all conditions. Testing and minor changes for WordPress 4.5.2.

09/29/15: Version 2.6

	Change to PHP5 style constructors for WordPress 4.3 and later. Additional fix to allow the plugin to work properly with https and http affliliate links.

03/13/14: Version 2.5

	Change to version number so newest stable version will properly appear on plugins site.

03/13/14: Version 2.4.1

	Minor fix to make behavior properly match documentation in Version 2.4.

03/13/14: Version 2.4

	Updated information on being tested up to WordPress 3.8.1. Changed voluntary giveback percentage from one to two. We appreciate your support.

01/04/10: Version 2.3

	Fix non-reaffiliating of Amazon links found after non-Amazon links under certain conditions

12/18/09: Version 2.2

	Add support for turning any work into an Amazon search link.

10/30/09: Version 1.0

	Initial release.

Copyright 2015  Dancing Mammoth  (email : p j d o l a n d [at] d a n c i n g m a m m o t h .com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

$WP_ASSOCIATIZER_DEBUG = !function_exists('add_filter');

class AmazonReAssociatizer
{
	var $adminoptionname = "DancingmammothPluginAmazonReAssociatizer";
	var $associatizerassociateid = 'associatizer-20';

	function __construct() {

	}

	function activate() {
		$this->getOptions();
	}

	function deactivate() {
		delete_option($this->adminoptionname);
	}

	function getOptions() {
		$adminoptions = array(
			'associateid' => $this->associatizerassociateid,
			'giveback'    => true,
		);
		$options = get_option($this->adminoptionname);
		if (empty($options)) {
			// options not set yet--initialize them
			update_option($this->adminoptionname, $adminoptions);
		} else {
			foreach ($options as $key => $value)
				if (array_key_exists($key, $adminoptions))
					$adminoptions[$key] = $value;
		}
		return $adminoptions;
	}

	function optionspage() {
		$options = $this->getOptions();
		if ($_SERVER['REQUEST_METHOD']==='POST' and isset($_POST['_wpnonce']) and wp_verify_nonce($_POST['_wpnonce'], $this->adminoptionname)) {
			if (isset($_POST['reassociatizer_associateid']))
				$options['associateid'] = $_POST['reassociatizer_associateid'];
			$options['giveback'] = isset($_POST['reassociatizer_giveback']);

			update_option($this->adminoptionname, $options);
?>
<div class="updated"><p><strong>Settings Updated</strong></p></div>
<?php
		}
?>
<div class="wrap">
	<form method="post" action="">
		<h2>Amazon Associate Associator</h2>

		<p><em>Note that you can turn any word or phrase into an Amazon.com
        search link with an associate id by surrounding them in three square
        brackets: <code>[[[Search term]]]</code> becomes
        <code>http://www.amazon.com/s/url=search-alias%3Daps&amp;field-keywords=Search+term</code></em></p>

		<h3>Amazon Associate Id</h3>
		<p>All Amazon product links in comments and post text will be
		rewritten to include the following associate id (even if they
		already include an associate id).</p>
		<p><label>Your Amazon Associate Id:
			<input type="text" name="reassociatizer_associateid" value="<?php echo htmlspecialchars($options['associateid'])?>" />
		</label></p>

		<h3>Give Back</h3>
		<p>If this setting is checked, five percent of the time the reassociated
		Amazon links will use the associate id of the author of this plugin.
		This is an easy way to give back a little.</p>
		<p><label>
			<input type="checkbox" name="reassociatizer_giveback" value="1" <?php if (!empty($options['giveback'])):?>checked="checked"<?php endif?> />
			Please Give Back?
		</label></p>

		<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce($this->adminoptionname)?>" />

		<div class="submit">
			<input type="submit" value="Update Settings" />
		</div>
	</form>
</div>
<?php
	}

	function filter($string) {
		$adminoptions = $this->getOptions();
		$string = $this->make_search_links($string);
		if ($adminoptions['associateid']) {
			$givebackpercent = ($adminoptions['giveback']) ? 5 : 0;
			$callbackobj = array(new Reassociatizer_callback($adminoptions['associateid'], $this->associatizerassociateid, $givebackpercent), 'callback');
			$string = $this->make_associates_links($string, $callbackobj);
		}
		return $string;
	}

	function make_search_links($string) {
		$regex = '/\[\[\[(.*?)\]\]\]/s';
		$string = preg_replace_callback($regex, array($this, 'make_search_links_callback'), $string);
		return $string;
	}

	function make_search_links_callback($match) {
		$searchterms = preg_replace('/\s+/', ' ', $match[1]);
		return '<a href="https://www.amazon.com/s/?url=search-alias%3Daps&field-keywords='.urlencode($searchterms).'">'.htmlspecialchars($match[1]).'</a>';
	}

	function make_associates_links($ret, $callbackobj=null) {
		$regex = '@
		(?:
			(?P<encl>["\'])? #optional quote to use for delimiting if available
			|\b      #otherwise any word boundary will do
		)
		(?P<nonquerystring>https?:// # IF (?P=nonproductttype) MATCHES, this captures all parts of the url before the query string (?-inclusive)
			(?:
				#hostname with optional subdomain
				(?P<host>
					(?:[^/\s]+(?(1)(?!\1))\.)?  # match any subdomains. Fail if space, slash, or enclosing delimiter are found (ensures non-amazon hostnames will not match)
					(?:(?P<Mhost>amzn\.com)|amazon\.(?:com|ca|fr|de|co\.jp|co\.uk)))
				/
				(?(4) #CONDITIONAL
					# IF the minimized base hostname matched (amzn.com), ASIN follows immediately
					(?P<Masin>[0-9A-Za-z]{10})
					|
					# OTHERWISE expect some stuff in between
					.*?(?<=/)(?:
							#product
							(?:(?:-|product|ASIN|dp)/(?P<asin>[0-9A-Za-z]{10})) # ASIN
							| # node or search
							(?:(?P<nonproducttype>[bs])(?:/|.*?)\?)
						)
				)
			))
		(?P<querystring>.*? #match the rest of the url. ONLY A QUERYSTRING IF (?P=nonproducttype)!!!
			(?= #assert (NOT MATCH) the closing delimiter
				(?(1) #if we matched an enclosing delimiter,
					(?:\1|[#]) # use it as the closing delimiter, or a hash from a hashid
					# otherwise stop matching the url at the first period, whitespace, hash part
					# of a url, nbsp entity, or lt sign (html open tag)
					|(?:[<\s.#]|&nbsp;|$)
				)
			)
		)
		@xi';
		$ret = preg_replace_callback($regex, $callbackobj, $ret);
		return $ret;
	}
}

class Reassociatizer_callback {
	var $associate;
	var $altassociate;
	var $altassociatepercentage;

	function Reassociatizer_callback($associate, $altassociate=null, $altassociatepercentage=0, $nosim=true) {
		$this->associate = $associate;
		$this->altassociate = $altassociate;
		$this->altassociatepercentage = (int) $altassociatepercentage;
	}

	function callback($match) {
		$match            = array(
			0                => $match[0],
			'encl'           => $match[1],
			'nonquerystring' => $match[2],
			'host'           => $match[3],
			'Mhost'          => $match[4],
			'Masin'          => $match[5],
			'asin'           => $match[6],
			'nonproducttype' => $match[7],
			'querystring'    => $match[8],
		);
		$encl = (empty($match['encl'])) ? '': $match['encl'];
		$associateid = ($this->altassociate and $this->altassociatepercentage and rand(1,100)<=$this->altassociatepercentage)
			? $this->altassociate : $this->associate;

		if (!empty($match['nonproducttype'])) {
			// search or node strings
			// for these, url remains the same except we rewrite the query string
			// to add/replace the tag= parameter
			if (!isset($match['querystring']) or empty($match['nonquerystring'])) {
				// Something went wrong and we didn't capture properly
				// return the match unchanged
				return $match[0];
			}
			$qs = html_entity_decode($match['querystring'], ENT_QUOTES);
			// If the query string was entity-encoded, we have to reencode at the end.
			$reencode = (strlen($qs)!==strlen($match['querystring']));

			# we don't use parse_str since php does extra munging which may mess
			# up query strings which have certain characters or duplicate keys
			$parts = explode('&', $qs);
			$newtag = "tag={$associateid}";
			$newparts = array();
			$tagfound = false;
			foreach ($parts as $part) {
				if (0===strpos($part, 'tag=')) {
					$tagfound = true;
					$newparts[] = $newtag;
					// don't break--'tag' may be in here more than once.
				} elseif ($part==='') {
					continue;
				} else {
					$newparts[] = $part;
				}

			}
			if (!$tagfound) {
				$newparts[] = $newtag;
			}

			$qs = implode('&', $newparts);
			if ($reencode) $qs = htmlspecialchars($qs, ENT_QUOTES);
			
			$newlink = "{$encl}{$match['nonquerystring']}{$qs}";
		} else {
			if (empty($match['Mhost'])) {
				$hostname = $match['host'];
				$ASIN = $match['asin'];
			} else {
				$hostname = 'www.amazon.com';
				$ASIN = $match['Masin'];
			}

			$scheme = parse_url($match['nonquerystring'], PHP_URL_SCHEME);
			$newlink = "{$encl}{$scheme}://{$hostname}/exec/obidos/ASIN/{$ASIN}/{$associateid}/";
		}
		return $newlink;
	}
}


if (class_exists('AmazonReAssociatizer')) {
	$dancingmammoth_amazonreassociatizer = new AmazonReAssociatizer();
}

if ($WP_ASSOCIATIZER_DEBUG) {
	$subj = <<<EOT
[[[Square bracket
search]]]
http://amazon.com/gp/product/0142000280
https://amazon.com/gp/product/0142000280
<a href='http://amazon.com/gp/product/020530902X&nbsp;#hashid'>http://amazon.com/gp/product/020530902X</a>
A sentence quoting "<a href="http://www.amazon.com/exec/obidos/ASIN/0142000280/OLDAFFILIATE/ref=nosim/?querystring=1#hashid" rel="nofollow">" a link</a>.
A sentence quoting "<a href="https://www.amazon.com/exec/obidos/ASIN/0142000280/OLDAFFILIATE/ref=nosim/?querystring=1#hashid" rel="nofollow">" a link</a>.
Ignore this: http://example.org/gp/product/020530902X
A normal link <a href="http://subdomain.amazon.de/o/ASIN/0142000280">normal</a>.
Old-style link <a href="https://amazon.com/exec/obidos/tg/detail/-/0142000280">old-style</a>
Visit this link: http://amzn.com/0142000280&nbsp;#hashid.
Visit this link: https://amzn.com/0142000280&nbsp;#hashid.
Or this one: http://amazon.co.uk/exec/obidos/ASIN/0142000280/OLDAFFILIATE/ref=nosim/
A node link: <a href='https://www.amazon.com/b?ie=UTF8&amp;node=163431&amp;tag=OLDAFFILIATE'></a>
A fancy node link: http://www.amazon.com/Science-Fiction-Fantasy-DVD/b/?ie=UTF8&node=163431
A tagged node link: https://www.amazon.com/Science-Fiction-Fantasy-DVD/b/?ie=UTF8&node=163431&tag=OLDAFFILIATE
A search link: http://www.amazon.com/s/ref=nb_ss?url=search-alias%3Dsoftware&field-keywords=blah+blah+blah&x=0&y=0
A tagged search link: https://www.amazon.com/s/ref=nb_ss?url=search-alias%3Dsoftware&field-keywords=blah+blah+blah&x=0&y=0&tag=associatizer-20
A link that should be ignored, followed by an amazon link:
<a href="http://www.example.org/v2/gwc_index.php?bogus=string">link text</a> non-link text <a href="http://www.amazon.com/exec/obidos/ASIN/B002DHC6FA/OLDAFFILIATE/ref=nosim/">amazon link text</a>
A two-level subdomain: http://deep.subdomain.amazon.co.uk/exec/obidos/ASIN/0142000280/OLDAFFILIATE/ref=nosim/
EOT
;
	$expected = <<<EOT
<a href="https://www.amazon.com/s/?url=search-alias%3Daps&field-keywords=Square+bracket+search&tag=associatizer-20">Square bracket
search</a>
http://amazon.com/exec/obidos/ASIN/0142000280/associatizer-20/
https://amazon.com/exec/obidos/ASIN/0142000280/associatizer-20/
<a href='http://amazon.com/exec/obidos/ASIN/020530902X/associatizer-20/#hashid'>http://amazon.com/exec/obidos/ASIN/020530902X/associatizer-20/</a>
A sentence quoting "<a href="http://www.amazon.com/exec/obidos/ASIN/0142000280/associatizer-20/#hashid" rel="nofollow">" a link</a>.
A sentence quoting "<a href="https://www.amazon.com/exec/obidos/ASIN/0142000280/associatizer-20/#hashid" rel="nofollow">" a link</a>.
Ignore this: http://example.org/gp/product/020530902X
A normal link <a href="http://subdomain.amazon.de/exec/obidos/ASIN/0142000280/associatizer-20/">normal</a>.
Old-style link <a href="https://amazon.com/exec/obidos/ASIN/0142000280/associatizer-20/">old-style</a>
Visit this link: http://www.amazon.com/exec/obidos/ASIN/0142000280/associatizer-20/&nbsp;#hashid.
Visit this link: https://www.amazon.com/exec/obidos/ASIN/0142000280/associatizer-20/&nbsp;#hashid.
Or this one: http://amazon.co.uk/exec/obidos/ASIN/0142000280/associatizer-20/
A node link: <a href='https://www.amazon.com/b?ie=UTF8&amp;node=163431&amp;tag=associatizer-20'></a>
A fancy node link: http://www.amazon.com/Science-Fiction-Fantasy-DVD/b/?ie=UTF8&node=163431&tag=associatizer-20
A tagged node link: https://www.amazon.com/Science-Fiction-Fantasy-DVD/b/?ie=UTF8&node=163431&tag=associatizer-20
A search link: http://www.amazon.com/s/ref=nb_ss?url=search-alias%3Dsoftware&field-keywords=blah+blah+blah&x=0&y=0&tag=associatizer-20
A tagged search link: https://www.amazon.com/s/ref=nb_ss?url=search-alias%3Dsoftware&field-keywords=blah+blah+blah&x=0&y=0&tag=associatizer-20
A link that should be ignored, followed by an amazon link:
<a href="http://www.example.org/v2/gwc_index.php?bogus=string">link text</a> non-link text <a href="http://www.amazon.com/exec/obidos/ASIN/B002DHC6FA/associatizer-20/">amazon link text</a>
A two-level subdomain: http://deep.subdomain.amazon.co.uk/exec/obidos/ASIN/0142000280/associatizer-20/
EOT
;

	$subj = $dancingmammoth_amazonreassociatizer->make_search_links($subj);
	$callbackobj = array(new Reassociatizer_callback('associatizer-20'), 'callback');
	$replacement = $dancingmammoth_amazonreassociatizer->make_associates_links($subj, $callbackobj);
	echo (($replacement===$expected) ? 'TEST PASS':"TEST FAIL (output follows):\n{$replacement}"),"\n";
} else {
	//initialize admin panel
	if (!function_exists('AmazonReAssociatizer_optionspage')) {
		function AmazonReAssociatizer_optionspage() {
			global $dancingmammoth_amazonreassociatizer;
			if (isset($dancingmammoth_amazonreassociatizer) and function_exists('add_options_page')) {
				add_options_page(
					'Amazon Associate Reassociatizer',
					'WP-Associatizer',
					9, basename(__FILE__),
					array($dancingmammoth_amazonreassociatizer, 'optionspage')
				);
			}
		}
	}

	//register callbacks
	if (isset($dancingmammoth_amazonreassociatizer)) {
		//actions
		add_action('admin_menu', 'AmazonReAssociatizer_optionspage');
		register_activation_hook(__FILE__,  array($dancingmammoth_amazonreassociatizer, 'activate'));
		register_activation_hook(__FILE__,  array($dancingmammoth_amazonreassociatizer, 'deactivate'));
		//filters
		add_filter('the_content',  array($dancingmammoth_amazonreassociatizer, 'filter'), 5);
		add_filter('comment_text', array($dancingmammoth_amazonreassociatizer, 'filter'), 5);
	}
}
