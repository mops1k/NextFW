<?php
namespace NextFW\Engine;

use NextFW\Engine as Engine;


trait TArrayAccess {
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            if(isset(self::${$offset})) self::${$offset} = $value;
            else $this->{$offset} = $value;
        } else {
            if(isset(self::${$offset})) self::${$offset} = $value;
            else $this->{$offset} = $value;
        }
    }
    public function offsetExists($offset) {
        if(isset(self::${$offset})) return isset(self::${$offset});
        else return isset($this->{$offset});
    }
    public function offsetUnset($offset) {
        if(isset(self::${$offset})) unset(self::${$offset});
        else unset($this->{$offset});
    }
    public function offsetGet($offset) {
        if(isset(self::${$offset})) return self::${$offset};
        else return $this->{$offset};
    }
}