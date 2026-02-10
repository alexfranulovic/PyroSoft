<?php
if(!isset($seg)) exit;

if(!function_exists('regressive_counter')) {
  /**
   * Generates a regressive counter section based on the provided attributes.
   *
   * @param array $Attr An array of attributes for the regressive counter.
   */
  function regressive_counter(array $Attr = [])
  {
    global $animations, $counter;

    if (!empty($Attr))
    {
      // Treat the time
      if (empty($Attr['final_moment']))
      {
        $final_moment = new DateTime(date('Y-m-d h:m:s', time()+20));
        $date         = $final_moment->format('Y-m-d h:m:s');
      }

      else {
        $final_moment = (array) (is_json($Attr['final_moment']) ? json_decode($Attr['final_moment']) : $Attr['final_moment']);
        $date         = "{$final_moment['date']} {$final_moment['time']}:00";
      }

      $result = "<article class='row justify-content-center ". ($animations ? 'animate-bottom' : '') ."' data-final-moment='$date'>";
      $result.= "
      <div class='content'>
      <span class='number' data-years>00</span>
      <span class='title'>Anos</span>
      </div>";
      $result.= "
      <div class='content'>
      <span class='number' data-months>00</span>
      <span class='title'>Meses</span>
      </div>";
      $result.= "
      <div class='content'>
      <span class='number' data-days>00</span>
      <span class='title'>Dias</span>
      </div>";
      $result.= "
      <div class='content'>
      <span class='number' data-hours>00</span>
      <span class='title'>Horas</span>
      </div>";
      $result.= "
      <div class='content'>
      <span class='number' data-minutes>00</span>
      <span class='title'>Minutos</span>
      </div>";
      $result.= "
      <div class='content'>
      <span class='number' data-seconds>00</span>
      <span class='title'>Segundos</span>
      </div>";
      $result.= "</article>";

      return $result;
    }
  }
}