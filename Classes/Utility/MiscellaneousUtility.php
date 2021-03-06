<?php
namespace FluidTYPO3\Flux\Utility;

/*
 * This file is part of the FluidTYPO3/Flux project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Flux\Form;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * MiscellaneousUtility Utility
 *
 * @package Flux
 * @subpackage Utility
 */
class MiscellaneousUtility {

	/**
	 * @var array
	 */
	private static $allowedIconTypes = array('png', 'gif');

	/**
	* @param string $icon
	* @return string
	*/
	public static function getIcon($icon) {
		return IconUtility::getSpriteIcon($icon);
	}

	/**
	* @param string $inner
	* @param string $uri
	* @param string $title
	* @return string
	*/
	public static function wrapLink($inner, $uri, $title) {
		return '<a href="#" onclick="window.location.href=\'' . htmlspecialchars($uri) . '\'" title="' . $title . '">' . $inner . '</a>';
	}

	/**
	 * Returns the icon for a template
	 * - checks and returns if manually set as option or
	 * - checks and returns Icon if it exists by convention in
	 *   EXT:$extensionKey/Resources/Public/Icons/$controllerName/$templateName.(png|gif)
	 *
	 * @param Form $form
	 * @return string|NULL
	 */
	public static function getIconForTemplate(Form $form) {
		if (TRUE === $form->hasOption(Form::OPTION_ICON)) {
			return $form->getOption(Form::OPTION_ICON);
		}
		if (TRUE === $form->hasOption(Form::OPTION_TEMPLATEFILE)) {
			$extensionKey = ExtensionNamingUtility::getExtensionKey($form->getExtensionName());
			$fullTemplatePathAndName = $form->getOption(Form::OPTION_TEMPLATEFILE);

			$templatePathParts = explode('/', $fullTemplatePathAndName);
			$templateName = pathinfo(array_pop($templatePathParts), PATHINFO_FILENAME);
			$controllerName = array_pop($templatePathParts);

			$iconPathAndName = ExtensionManagementUtility::extPath($extensionKey, 'Resources/Public/Icons/' . $controllerName . '/' . $templateName);
			foreach (self::$allowedIconTypes as $iconType) {
				$potentialIcon = $iconPathAndName . '.' . $iconType;
				if (is_file($potentialIcon)) {
					$positionOfResourceInIconPath = strpos($potentialIcon, 'Resources/Public/Icons/');
					return '../' . ExtensionManagementUtility::siteRelPath($extensionKey) . substr($potentialIcon, $positionOfResourceInIconPath);
				}
			}
		}
		return NULL;
	}

	public static function cleanFlexFormXml($xml, array $removals = array()) {
		$dom = new \DOMDocument();
		$dom->loadXML($xml);
		$dom->preserveWhiteSpace = FALSE;
		$dom->formatOutput = TRUE;
		foreach ($dom->getElementsByTagName('field') as $fieldNode) {
			/** @var \DOMElement $fieldNode */
			if (TRUE === in_array($fieldNode->getAttribute('index'), $removals)) {
				$fieldNode->parentNode->removeChild($fieldNode);
			}
		}
		// Assign a hidden ID to all container-type nodes, making the value available in templates etc.
		foreach ($dom->getElementsByTagName('el') as $containerNode) {
			/** @var \DOMElement $containerNode */
			$hasIdNode = FALSE;
			if (0 < $containerNode->attributes->length) {
				// skip <el> tags reserved for other purposes by attributes; only allow pure <el> tags.
				continue;
			}
			foreach ($containerNode->childNodes as $fieldNodeInContainer) {
				/** @var \DOMElement $fieldNodeInContainer */
				if (FALSE === $fieldNodeInContainer instanceof \DOMElement) {
					continue;
				}
				$isFieldNode = ('field' === $fieldNodeInContainer->tagName);
				$isIdField = ('id' === $fieldNodeInContainer->getAttribute('index'));
				if ($isFieldNode && $isIdField) {
					$hasIdNode = TRUE;
					break;
				}
			}
			if (FALSE === $hasIdNode) {
				$idNode = $dom->createElement('field');
				$idIndexAttribute = $dom->createAttribute('index');
				$idIndexAttribute->nodeValue = 'id';
				$idNode->appendChild($idIndexAttribute);
				$valueNode = $dom->createElement('value');
				$valueIndexAttribute = $dom->createAttribute('index');
				$valueIndexAttribute->nodeValue = 'vDEF';
				$valueNode->appendChild($valueIndexAttribute);
				$valueNode->nodeValue = sha1(uniqid('container_', TRUE));
				$idNode->appendChild($valueNode);
				$containerNode->appendChild($idNode);
			}
		}
		$xml = $dom->saveXML();
		// hack-like pruning of empty-named node inserted when removing objects from a previously populated Section
		$xml = str_replace('<field index=""></field>', '', $xml);
		return $xml;
	}

}
