<?php
/**
 * @file
 * Contains \Drupal\cleantaxonomy\Controller\TaxonomyList.
 */

namespace Drupal\cleantaxonomy\Controller;

use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;	

/**
 * Provides route responses for the cleantaxonomy module.
 */
class TaxonomyList extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Constructs a cleantaxonomy object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function TaxonomyTermsList() {
    $header = [
      t('Term ID'),
      t('Taxonomy Term'),
      t('Vocabulary'),
      [
        'data' => t('No. of nodes attached'),
        'sort' => 'desc'
      ],
      [
        'data' => t('Operation'),
        'colspan' => '4'
      ],
    ];
    $id_name = db_select('taxonomy_term_field_data')
      ->fields('taxonomy_term_field_data',['tid','name', 'vid'])
      ->execute();
    $id_name_records=$id_name->fetchAll();
    $id_name_values = [];
    $size=0;
    foreach ($id_name_records as $id_name_record) {
      $id_name_values[$id_name_record->tid] = $id_name_record;
      $size++;
    } 
    $node_count=db_query("SELECT count(nid) AS n,tid FROM {taxonomy_index} GROUP BY tid",[]);
    $node_count_records=$node_count->fetchAll();
    foreach ($node_count_records as $key=>$node_count_record) {
      $tid_value[$key]= $node_count_record->tid; 
      $nid_count_value[$key]= $node_count_record->n;
    }
    $j=0;
    for ($i=1; $i <=$size; $i++) {
      if($tid_value[$j]!=$i)  {
        $nodes[$i]=0;
      }
      else {
        $nodes[$i]=$nid_count_value[$j];
        $j++;
      }
    }
    foreach ($id_name_values as $key=>$id_name_value) {
      $tid[$key]=$id_name_value->tid;
      $name[$key]=$id_name_value->name;
      $vocab[$key]=$id_name_value->vid;
    }

    $s=1;
    $rows = [];
    for($n=1;$n<=$size;$n++) {
      if($vocab[$n]=='tags') {
        $rows[] = [
            'data' => [
              t($tid[$n]),
              t($name[$n]),
              t($vocab[$n]),
               print_r($nodes[$s],$s++),
              \Drupal::l(t('View'), Url::fromUri('internal:/taxonomy/term/'.$tid[$n],[$tid[$n]])),
              \Drupal::l(t('Edit'), Url::fromUri('internal:/taxonomy/term/'.$tid[$n].'/edit')),
              \Drupal::l(t('Delete'), Url::fromUri('internal:/taxonomy/term/'.$tid[$n].'/delete')),
              \Drupal::l(t('Replace'),new Url('cleantaxonomy.admin_cleantaxonomy.tid.replace',['tid'=>$tid[$n]])),
            ],
          ];
      } 
      else {
        $rows[] = [
          'data' => [
            t($tid[$n]),
            t($name[$n]),
            t($vocab[$n]),
            print_r($nodes[$s],$s++),
            \Drupal::l(t('View'), Url::fromUri('internal:/taxonomy/term/'.$tid[$n],[$tid[$n]])),
            \Drupal::l(t('Edit'), Url::fromUri('internal:/taxonomy/term/'.$tid[$n].'/edit')),
            \Drupal::l(t('Delete'), Url::fromUri('internal:/taxonomy/term/'.$tid[$n].'/delete')),
          ],
        ];
      }
    }
    $build['admin_cleantaxonomy_list_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No taxonomy terms available.'),
    ];
    $build['admin_cleantaxonomy_list_pager'] = ['#theme' => 'pager'];
    return $build;
  }
}