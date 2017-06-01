<?php
namespace Drupal\we_megamenu;

use Drupal\Core\Menu;
use Drupal\Core\Menu\MenuTreeParameters;

class WeMegaMenuBuilder {
  /**
   * Get menu tree we_megamenu.
   *
   * @param string $menu_name as machiname of drupal menu
   * @param array $items list of child drupal menu
   * @param integer $level
   * @return array
   */
  public static function get_menu_tree($menu_name, $items = [], $level = 0) {
    $result = [];
    if ($level == 0) {
      $menu_active_trail = \Drupal::service('menu.active_trail')->getActiveTrailIds($menu_name);
      $menu_tree_parameters = (new MenuTreeParameters)->setActiveTrail($menu_active_trail)->onlyEnabledLinks();
      $tree = \Drupal::menuTree()->load($menu_name, $menu_tree_parameters);
      foreach ($tree as $item) {
        $route_name = $item->link->getPluginDefinition()['route_name'];
        $result[] = [
          'derivativeId' => $item->link->getDerivativeId(),
          'title' => $item->link->getTitle(),
          'level' => $level,
          'description' => $item->link->getDescription(),
          'weight' => $item->link->getWeight(),
          'url' => $item->link->getUrlObject()->toString(),
          'subtree' => self::get_menu_tree($menu_name, $item, $level + 1),
          'route_name' => $route_name,
          'in_active_trail' => $item->inActiveTrail,
          'plugin_id' => $item->link->getPluginId(),
        ];
      }
    } else {
      if ($items->hasChildren) {
        foreach ($items->subtree as $key_item => $item) {
          $route_name = $item->link->getPluginDefinition()['route_name'];
          $result[] = [
            'derivativeId' => $item->link->getDerivativeId(),
            'title' => $item->link->getTitle(),
            'level' => $level,
            'description' => $item->link->getDescription(),
            'weight' => $item->link->getWeight(),
            'url' => $item->link->getUrlObject()->toString(),
            'subtree' => self::get_menu_tree($menu_name, $item, $level + 1),
            'route_name' => $route_name,
            'in_active_trail' => $item->inActiveTrail,
            'plugin_id' => $item->link->getPluginId(),
          ];
        }
      }
    }
    return $result;
  }

  /**
   * Get menu tree sorted by weight ascending.
   *
   * @param string $menu_name as machiname of drupal menu
   * @param array $items list of child drupal menu
   * @param integer $level
   * @return array
   */
  public static function get_menu_tree_order($menu_name, $items = [], $level = 0) {
    $menu = self::get_menu_tree($menu_name, $items = [], $level = 0);
    return self::sort_menu_deep($menu);
  }

  /**
   * Sort list child menu
   *
   * @param string $menu as array item
   * @return array
   */
  public static function sort_menu_deep($menu) {
    if (is_array($menu)) {
      $menu = self::sort_menu($menu);
      foreach ($menu as $key_item => $item) {
        if (isset($item['subtree'])) {
          $menu[$key_item]['subtree'] = self::sort_menu_deep($item['subtree']);
        }
      }
      return $menu;
    }
    return [];
  }

  /**
   * Sort menu by weight
   *
   * @param string $menu as array item
   * @return array
   */
  public static function sort_menu($menu) {
    for ($i = 0; $i < sizeof($menu); $i++) { 
      for ($j = $i + 1; $j < sizeof($menu); $j++) { 
        if ($menu[$i]['weight'] > $menu[$j]['weight']) {
          $menu_tmp = $menu[$i];
          $menu[$i] = $menu[$j];
          $menu[$j] = $menu_tmp;
        }
      }
    }
    return $menu;
  }

  /**
   * Get all block of drupal
   *
   * @staticvar array $_list_blocks_array
   * @return array
   */
  public static function getAllBlocks() {
    static $_list_blocks_array = [];
    if (empty($_list_blocks_array)) {
      $theme_default = \Drupal::config('system.theme')->get('default');
      $block_storage = \Drupal::entityManager()->getStorage('block');
      $entity_ids = $block_storage->getQuery()->condition('theme', $theme_default)->execute();
      $entities = $block_storage->loadMultiple($entity_ids);
      $_list_blocks_array = [];
      foreach ($entities as $block_id => $block) {
        if ($block->get('settings')['provider'] != 'we_megamenu') {
          $_list_blocks_array[$block_id] = $block->label();
        }
      }
      asort($_list_blocks_array);
    }
    return $_list_blocks_array;
  }

  /**
   * Check router exists
   *
   * @param string $name as router name
   * @return integer (equa 0 is not exists)
   */
  public static function routeExists($name) {
    $route_provider = \Drupal::service('router.route_provider');
    $route_provider = $route_provider->getRoutesByNames([$name]);
    return sizeof($route_provider);
  }

  /**
   * Render drupal block
   *
   * @param string $bid
   * @param bool $title_enable
   * @param string $section
   * @return string [markup html]
   */
  public static function renderBlock($bid, $title_enable = TRUE, $section = '') {
    $html = '';
    if ($bid && !empty($bid)) {
      $block = \Drupal\block\Entity\Block::load($bid);
      $title = $block->label();
      $block_content = \Drupal::entityManager()
        ->getViewBuilder('block')
        ->view($block);

      if ($section == 'admin') {
        $html .= '<span class="close icon-remove" title="Remove this block">&nbsp;</span>';
      }

      $html .= '<div class="type-of-block">';
        $html .= '<div class="block-inner">';
          $html .= $title_enable ? '<h2>' . $title . '</h2>' : '';
          $html .= render($block_content);
        $html .= '</div>';
      $html .= '</div>';
    }
    return $html;
  }

  /**
   * Render WeebPal Mega Menu blocks
   *
   * @param string $menu_name
   * @param string $theme
   * @return array
   */
  public static function renderWeMegaMenuBlock($menu_name, $theme) {
    return [
      '#theme' => 'we_megamenu_frontend',
      '#block_theme' => $theme,
      '#menu_name' => $menu_name,
      '#section' => 'admin',
      '#blocks' => WeMegaMenuBuilder::getAllBlocks(),
    ];
  }

  /**
   * Load config WeebPal Mega Menu
   *
   * @param string $menu_name
   * @param string $theme
   * @return string || bool
   */
  public static function loadConfig($menu_name = '', $theme = '') {
    if (!empty($menu_name) && !empty($theme)) {
      $query = \Drupal::database()->select('we_megamenu', 'km');
      $query->addField('km', 'data_config');
      $query->condition('km.menu_name', $menu_name);
      $query->condition('km.theme', $theme);
      $query->range(0, 1);
      return json_decode(json_decode($query->execute()->fetchField()));
    }
    return FALSE;
  }

  # Validate order item

  /**
   * get trail array
   *
   * @return array
   */
  public static function buildPageTrail($menu_items) {
    $trail = [];
    foreach ($menu_items as $key_item => $item) {
      $plugin_id = $item['plugin_id'];
      $check_is_front_page = \Drupal::service('path.matcher')->isFrontPage();
      $route_name = $item['route_name'];

      if ($route_name == '<front>' && $check_is_front_page) {
        $trail[$plugin_id] = $item;
      } elseif (isset($item['in_active_trail']) && $item['in_active_trail'] == 1) {
        $trail[$plugin_id] = $item;
      }

      if (isset($item['subtree']) && sizeof($item['subtree'])) {
        $trail += self::buildPageTrail($item['subtree']);
      }
    }
    return $trail;
  }

  /**
   * render all drupal view
   *
   * @return string [markup html]
   */
  public static function renderView() {
    $entity_manager = \Drupal::entityManager();
    $views = $entity_manager->getStorage('view')->loadMultiple();
    foreach ($views as $key => $view) {
      $view = \Drupal\views\Views::getView($key);
      $a = $view->render();
      if ($a) {
        echo drupal_render($view);
        exit;
      }
    }   
  }
}