<?php
namespace Drupal\we_megamenu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\we_megamenu\WeMegaMenuBuilder;
use Drupal\block\Controller\BlockListController;

/**
 * Controller routines for block example routes.
 */
class WeMegaMenuAdminController extends ControllerBase {
  /**
   * A function build page backend
   * @param string $menu_name
   * @return string [markup]
   */
  public function configWeMegaMenu($menu_name) {
    $tree = WeMegaMenuBuilder::get_menu_tree_order($menu_name);

    $build = [];
    $build['we_megamenu'] = [
      '#theme' => 'we_megamenu_backend',
      '#menu_name' => $menu_name,
      '#items' => $tree,
      '#blocks' => WeMegaMenuBuilder::getAllBlocks(),
      '#block_theme' => \Drupal::config('system.theme')->get('default'),
    ];

    $build['we_megamenu']['#attached']['library'][] = 'we_megamenu/form.we-mega-menu-backend';
    $abs_url_save_config = \Drupal::url('we_megamenu.admin.save', [], ['absolute' => TRUE]);
    $abs_url_reset_config = \Drupal::url('we_megamenu.admin.reset', [], ['absolute' => TRUE]);
    $abs_url_icons_config = \Drupal::url('we_megamenu.geticons', [], ['absolute' => TRUE]);
    $build['#attached']['drupalSettings']['WeMegaMenu']['saveConfigWeMegaMenuURL'] = $abs_url_save_config;
    $build['#attached']['drupalSettings']['WeMegaMenu']['resetConfigWeMegaMenuURL'] = $abs_url_reset_config;
    $build['#attached']['drupalSettings']['WeMegaMenu']['iconsWeMegaMenuURL'] = $abs_url_icons_config;
    return $build;
  }

  /**
   * A function ajax save menu config
   */
  
  public function saveConfigWeMegaMenu() {
    if (isset($_POST['action']) && $_POST['action'] == 'save') {
      $data_config = json_encode($_POST['data_config']);
      $theme = $_POST['theme'];
      $menu_name = $_POST['menu_name'];
      $result = \Drupal::service('database')
            ->merge('we_megamenu')
            ->key([
              'menu_name' => $menu_name,
              'theme' => $theme
            ])
            ->fields([
              'data_config' => $data_config,
            ])->execute();
    }
    exit;
  }

  /**
   * A function reset menu config
   * @return string [markup]
   */
  public function resetConfigWeMegaMenu() {
    if (isset($_POST['action']) && $_POST['action'] == 'reset' && isset($_POST['menu_name']) && isset($_POST['theme'])) {
      $theme_array = WeMegaMenuBuilder::render_we_megamenu_block($_POST['menu_name'], $_POST['theme']);
      $markup = render($theme_array); 
      echo $markup; exit;
    }

    if (isset($_POST['action']) && $_POST['action'] == 'reset-to-default' && isset($_POST['menu_name']) && isset($_POST['theme'])) {
      $query = \Drupal::database()->delete('we_megamenu');
      $query->condition('menu_name', $_POST['menu_name']);
      $query->condition('theme', $_POST['theme']);
      $result = $query->execute();
      $theme_array = WeMegaMenuBuilder::render_we_megamenu_block($_POST['menu_name'], $_POST['theme']);
      $markup = render($theme_array); 
      echo $markup; exit;
      exit;
    }
    exit;
  }

  /**
   * Render block from post variable ajax
   * @param string $menu_name
   * @return string [markup]
   */
  public function renderBlock() {
    $title = TRUE;
    if ($_POST['title'] == 0) {
      $title = FALSE;
    }
    
    if (isset($_POST['bid']) && isset($_POST['section']) && !empty($_POST['bid'])) {
      echo WeMegaMenuBuilder::renderBlock($_POST['bid'], $title, isset($_POST['section']));
    } else {
      echo "";
    }
    exit;
  }

  /**
   * Render page list menu backend
   */
  public function listWeMegaMenus() {
    $menus = menu_ui_get_menus();
    $rows = [];
    foreach ($menus as $name => $title) {
      $row = [
        'menu-name' => $name,
        'menu-title' => $title
      ];

      $dropbuttons = [
        '#type' => 'operations',
        '#links' => [
          'config' => [
            'url' => new Url('we_megamenu.admin.configure', ['menu_name' => $name]),
            'title' => 'Config'
          ],
          'edit' => [
            'url' => new Url('entity.menu.edit_form', ['menu' => $name]),
            'title' => 'Edit links'
          ],
        ],
      ];
      $row['menu-operations'] = ['data' => $dropbuttons];
      $rows[] = $row;
    }
    // Prepare label for headers.
    $header = [
      'menu-machine-name' => t('Machine Name'),
      'menu-name' => t('Menu Name'),
      'menu-options' => t('Options')
    ];

    return [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No WeebPal Mega Menu block available. <a href="@link">Add Menu</a>.', ['@link' => \Drupal::url('entity.menu.add_form')]),
      '#attributes' => ['id' => 'we_megamenu'],
    ];
  }

  /**
   * Render list icon font awesome
   */
  public function getIcons() {
    $file = DRUPAL_ROOT . '/' . drupal_get_path('module', 'we_megamenu') . '/assets/resources/icon.wemegamenu';
    $fh = fopen($file,'r');
    $result = [];
    while ($line = fgets($fh)) {
      $result[] = trim($line);
    }
    fclose($fh);
    echo json_encode($result);
    exit;
  }
}
