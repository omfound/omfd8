<?php
/**
 * @file
 * Contains \Drupal\omf_blocks\Plugin\Block\ContentBlock.
 */
namespace Drupal\omf_blocks\Plugin\Block;
use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Session\AccountInterface;

/**
 * Content Block
 *
 * @Block(
 *   id = "content_block",
 *   admin_label = @Translation("Content Block")
 * )
 */
class ContentBlock extends BlockBase {
  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    $this->configuration['label'] = t('Content Block');
    return array(
      '#children' => 'This is a block!',
    );
  }

  /**
   * Implements \Drupal\block\BlockBase::access().
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('access content');
  }
}
