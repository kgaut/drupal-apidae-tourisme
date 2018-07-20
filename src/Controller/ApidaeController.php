<?php

namespace Drupal\apidae_tourisme\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\apidae_tourisme\ApidaeSync;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ApidaeController.
 */
class ApidaeController extends ControllerBase {

  /**
   * Drupal\apidae_tourisme\ApidaeSync definition.
   *
   * @var \Drupal\apidae_tourisme\ApidaeSync
   */
  protected $apidaeToursimeSync;

  /**
   * Constructs a new ApidaeController object.
   */
  public function __construct(ApidaeSync $apidae_toursime_sync) {
    $this->apidaeToursimeSync = $apidae_toursime_sync;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('apidae_toursime.sync')
    );
  }

  public function sync($objet) {
    $objet = Node::load($objet);
    if($objet->getType() === 'objet_touristique') {
      $this->apidaeToursimeSync->sync(TRUE, [$objet->get('field_id_ws')->value]);
    }
    return new RedirectResponse(Url::fromRoute('view.objets_tourisques.page')->toString());
  }

}
