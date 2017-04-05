<?php
namespace Ari_Adminer\Models;

use Ari\Models\Model as Model;
use Ari_Adminer\Models\Connections as Connections_Model;

class Adminer_Runner extends Model {
    public function data() {
        $connections_model = new Connections_Model(
            array(
                'class_prefix' => $this->options->class_prefix,

                'disable_state_load' => true,
            )
        );

        $connections = $connections_model->items();

        $data = array(
            'connections' => $connections,
        );

        return $data;
    }
}
