<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * TYPOlight webCMS
 * Copyright (C) 2005 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at http://www.gnu.org/licenses/.
 *
 * PHP version 5
 * @copyright  Andreas Schempp 2008
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @license    LGPL
 */


class FormAjaxRating extends Widget
{
	protected $strTemplate = 'form_widget';
	
	private $intRatingUnitWidth = 30;
	
	
	public function validate()
	{
		return parent::validate();
	}
	
	
	public function generate()
	{
		$GLOBALS['TL_CSS']['rating'] = 'system/modules/ajaxrating/html/rating.css';
		
		$intRatingWidth = $this->value * $this->intRatingUnitWidth;
		
		$strUrl  = preg_replace('@(\?|&)q=[^&]*&j=[^&]@', '', $this->Environment->request);
		$strUrl .= strpos($strUrl, '?') === false ? '?' : '&';
		
		
		$return  = '<div class="ratingblock">';
		$return .= '<div id="unit_long'.$this->strId.'">';
		$return .= '<ul id="unit_ul'.$this->strId.'" class="unit-rating" style="width:'.$this->intRatingUnitWidth*$this->size.'px;">';
		$return .= '<li class="current-rating" style="width:'.$intRatingWidth.'px;">Currently '.$this->value.'/'.$this->size.'</li>';
		
		if (!$this->voted)
		{
			for( $i=1; $i<=$this->size; $i++)
			{
				$return .= '<li class="rater"><a href="'. $strUrl .'q='.$this->strName.'&amp;j='.$i.'" title="'.sprintf($GLOBALS['TL_LANG']['MSC']['valueOutOf'], $i, $this->size).'" class="r'.$i.'-unit rater" rel="nofollow" onclick="return false">'.$i.'</a></li>';
			}
		}
		
		$return .= '</ul></div></div>';
		
		$return .= "
		<script type=\"text/javascript\">
			window.addEvent('domready', function() {

				$$('#unit_ul" . $this->strId . " a.rater').addEvent('click', function() {
				
					new Ajax(this.href + '&isAjax=1', {
						onComplete: function(txt, xml) {
							$('unit_ul" . $this->strId . "').setHTML('<li class=\"current-rating\" style=\"width:' + (txt * " . $this->intRatingUnitWidth . ") + 'px;\">Currently ' + txt + '/".$this->size."</li>');
						}
					}).request();
					
					$('unit_ul" . $this->strId . "').setHTML('<div class=\"loading\"></div>');
					
				});
				
			});
		</script>
		";
		
		return $return;
	}
}