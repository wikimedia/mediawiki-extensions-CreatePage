<?php
/**
 * MediaWiki extension CreatePage.
 *
 * Copyright (C) 2012, Ike Hecht & Jeroen De Dauw
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @ingroup Extensions
 * @author Ike Hecht
 * @author Jeroen De Dauw
 * @version 0.1
 * @link https://www.mediawiki.org/wiki/Extension:Create_Page Documentation
 * @license https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL-3.0-or-later
 */

/**
 * This documentation group collects source code files belonging to Create Page.
 *
 * @defgroup CreatePage Create Page
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

global $wgVersion, $wgExtensionCredits, $wgExtensionMessagesFiles, $wgAutoloadClasses;
global $wgSpecialPages, $wgHooks, $wgResourceModules;

// Needs to be 1.18c because version_compare() works in confusing ways.
if ( version_compare( $wgVersion, '1.18c', '<' ) ) {
	die( '<b>Error:</b> Create Page requires MediaWiki 1.18 or above.' );
}

define( 'CP_VERSION', '0.4.0' );

$wgExtensionCredits['other'][] = [
	'path' => __FILE__,
	'name' => 'Create Page',
	'version' => CP_VERSION,
	'author' => [
		'[https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw]',
		'Ike Hecht'
	],
	'url' => 'https://www.mediawiki.org/wiki/Extension:Create_Page',
	'descriptionmsg' => 'cp-desc'
];
// Configuration
/* Set to true to edit existing pages. */
$wgCreatePageEditExisting = false;
/* Set to true to redirect to VisualEditor for page creation. */
$wgCreatePageUseVisualEditor = false;

// i18n
$wgMessagesDirs['CreatePage'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['CreatePageAlias'] = __DIR__ . '/CreatePage.alias.php';
$wgExtensionMessagesFiles['CreatePageMagic'] = __DIR__ . '/CreatePage.magic.php';

$wgAutoloadClasses['SpecialCreatePageRedirect'] = __DIR__ . '/SpecialCreatePageRedirect.php';
$wgSpecialPages['CreatePageRedirect'] = 'SpecialCreatePageRedirect';

$wgResourceModules['ext.createPage'] = [
	'scripts' => 'modules/createPage.searchSuggest.js',
	'dependencies' => 'mediawiki.searchSuggest',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'CreatePage'
];

$wgHooks['ParserFirstCallInit'][] = function ( Parser &$parser ) {
	$parser->setFunctionHook( 'createpage', function ( Parser $parser, PPFrame $frame, array $args ) {
		$html = Html::openElement( 'form', [
			'action' => SpecialPage::getTitleFor( 'CreatePageRedirect' )->getLocalURL(),
			'method' => 'post',
			'style' => 'display: inline',
			'class' => 'createpageform'
			] );

		$html .= Html::input(
			'pagename',
			array_key_exists( 1, $args ) ? trim( $frame->expand( $args[1] ) ) : '', 'text',
				[ 'class' => 'pagenameinput' ]
		);

		if ( array_key_exists( 0, $args ) ) {
			$namespaceText = trim( $frame->expand( $args[0] ) );
			$attribs = [];

			// Find the ID of this namespace, if there is one.
			$namespaceID = MWNamespace::getCanonicalIndex( strtolower( $namespaceText ) );
			if ( $namespaceID != 0 ) {
				$attribs['nsid'] = $namespaceID;
			}
			$html .= Html::hidden( 'pagens', $namespaceText, $attribs );
		}

		$html .= '&#160;';

		$html .= Html::input(
			'createpage',
			array_key_exists( 2, $args ) ? trim( $frame->expand( $args[2] ) ) :
					wfMessage( 'cp-create' )->text(), 'submit'
		);

		if ( array_key_exists( 3, $args ) ) {
			$html .= Html::hidden( 'preload', trim( $frame->expand( $args[3] ) ) );
		}

		$html .= '</form>';

		return $parser->insertStripItem( $html );
	}, Parser::SFH_OBJECT_ARGS );

	return true;
};

$wgHooks['BeforePageDisplay'][] = function ( OutputPage &$out ) {
	$out->addModules( 'ext.createPage' );
	return true;
};
