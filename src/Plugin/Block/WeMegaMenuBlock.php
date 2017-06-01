<?php
# Structure Of WeebPal Mega Menu
// we-mega-menu.html.twig
//     we-mega-menu-ul.html.
//         we-mega-menu-li.html.twig
//             we-mega-menu-submenu.html.twig
//                 we-mega-menu-row.html.twig
//                     we-mega-menu-col.html.twig
//                         we-mega-menu-ul.html.twig
//                             ...
//                     we-mega-menu-col.html.twig
//                         we-mega-menu-ul.html.twig
//                             ...
//                     we-mega-menu-col.html.twig
//                         we-mega-menu-block.html.twig
//                         we-mega-menu-block.html.twig

namespace Drupal\we_megamenu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Menu;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Form\FormStateInterface;
use Drupal\we_megamenu\WeMegaMenuBuilder;

/**
 * Provides a 'WeebPal Mega Menu' Block.
 *
 * @Block(
 *   id = "we_megamenu_block",
 *   admin_label = @Translation("WeebPal Mega Menu"),
 *   category = @Translation("WeebPal Mega Menu"),
 *   deriver = "Drupal\we_megamenu\Plugin\Derivative\WeMegaMenuBlock",
 * )
 */
class WeMegaMenuBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#theme' => 'we_megamenu_frontend',
      '#menu_name' => $this->getDerivativeId(),
      '#blocks' => WeMegaMenuBuilder::getAllBlocks(),
      '#block_theme' => \Drupal::config('system.theme')->get('default'),
      '#attached' => [
        'library' => [
          'we_megamenu/form.we-mega-menu-frontend'
        ]
      ],
      '#cache' => [
        'max-age' => 0,
      ]
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   *  Default cache is disabled. 
   * 
   * @param array $form
   * @param \Drupal\we_megamenu\Plugin\Block\FormStateInterface $form_state
   * @return 
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $rebuild_form = parent::buildConfigurationForm($form, $form_state);
    $rebuild_form['cache']['max_age']['#default_value'] = 0;
    return $rebuild_form;
  }
}