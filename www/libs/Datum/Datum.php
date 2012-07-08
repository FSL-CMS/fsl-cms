<?php

class Datum { //třída datum()

    static public $nazvy, $predvcera, $vcera, $dnes, $zitra, $pozitri, $datum, $datum_stamp, $den, $mesic, $rok, $hodina, $minuta, $sekunda, $den_v_tydnu, $format_vystupu, $den_stamp, $nedavno, $predlozka;

function __construct() {

     self::$nazvy['mesic'] = array( 1 => 'leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec' );
     self::$nazvy['mesice'] = array( 1 => 'ledna', 'února', 'března', 'dubna', 'května', 'června', 'července', 'srpna', 'září', 'října', 'listopadu', 'prosince' );
     self::$nazvy['mesici'] = array( 1 => 'lednu', 'únoru', 'březnu', 'dubnu', 'květnu', 'červnu', 'červenci', 'srpnu', 'září', 'říjnu', 'listopadu', 'prosinci' );
     self::$nazvy['den_v_tydnu'] = array( 'neděle', 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota', 'pondeli'=>'pondělí', 'utery'=>'úterý', 'streda'=>'středa', 'ctvrtek'=>'čtvrtek', 'patek'=>'pátek', 'sobota'=>'sobota', 'nedele'=>'neděle', );
     self::$nazvy['dne_v_tydnu'] = array( 'neděli', 'pondělí', 'úterý', 'středu', 'čtvrtek', 'pátek', 'sobotu' );
     //self::$nazvy[] = array();

     self::$predvcera['den'] = date( 'j', mktime (0, 0, 0, date('m'), date('d')-2, date('Y')) );
     self::$predvcera['mesic'] = date( 'n', mktime (0, 0, 0, date('m'), date('d')-2, date('Y')) );
     self::$predvcera['rok'] = date( 'Y', mktime (0, 0, 0, date('m'), date('d')-2, date('Y')) );

     self::$vcera['den'] = date( 'j', mktime (0, 0, 0, date('m'), date('d')-1, date('Y')) );
     self::$vcera['mesic'] = date( 'n', mktime (0, 0, 0, date('m'), date('d')-1, date('Y')) );
     self::$vcera['rok'] = date( 'Y', mktime (0, 0, 0, date('m'), date('d')-1, date('Y')) );

     self::$dnes['den'] = date( 'j', mktime (0, 0, 0, date('m'), date('d'), date('Y')) );
     self::$dnes['mesic'] = date( 'n', mktime (0, 0, 0, date('m'), date('d'), date('Y')) );
     self::$dnes['rok'] = date( 'Y', mktime (0, 0, 0, date('m'), date('d'), date('Y')) );
//     self::$dnes['hodina'] = date( 'H', mktime (0, 0, 0, date('m'), date('d'), date('Y')) );
     self::$dnes['dnes'] = date( 'Y-m-d' );
     self::$dnes['ted'] = date('Y-m-d H:i:s');

     self::$zitra['den'] = date( 'j', mktime (0, 0, 0, date('m'), date('d')+1, date('Y')) );
     self::$zitra['mesic'] = date( 'n', mktime (0, 0, 0, date('m'), date('d')+1, date('Y')) );
     self::$zitra['rok'] = date( 'Y', mktime (0, 0, 0, date('m'), date('d')+1, date('Y')) );

     self::$pozitri['den'] = date( 'j', mktime (0, 0, 0, date('m'), date('d')+2, date('Y')) );
     self::$pozitri['mesic'] = date( 'n', mktime (0, 0, 0, date('m'), date('d')+2, date('Y')) );
     self::$pozitri['rok'] = date( 'Y', mktime (0, 0, 0, date('m'), date('d')+2, date('Y')) );

}

static function rozdel() {

     if( !preg_match( '~^[0-9]{4}-[0-9]{1,2}-[0]{1,2}( 00:00:00)?$~', self::$datum ) ) {
          self::$datum_stamp = strtotime( self::$datum ); // časové razítko

          self::$den = date( 'j', self::$datum_stamp ); // den bez úvodní nuly
          self::$mesic = date( 'n', self::$datum_stamp ); // mesic bez úvodní nuly
          self::$rok = date( 'Y', self::$datum_stamp ); // rok RRRR
          self::$hodina = date( 'G', self::$datum_stamp ); // hodiny bez úvodních nul
          self::$minuta = date( 'i', self::$datum_stamp ); // minuty MM
          self::$sekunda = date( 's', self::$datum_stamp ); // sekundy SS
          self::$den_v_tydnu = date( 'w', self::$datum_stamp ); // sekundy SS

     } else {
     // 2006-09-00
          self::$den = 0; //substr( 9, 10, self::$datum )*1;
          self::$mesic = substr( self::$datum, 5, 2 )*1;
          self::$rok = substr( self::$datum, 0, 4 )*1;
          self::$hodina = substr( self::$datum, 12, 2 )*1;
          self::$minuta = substr( self::$datum, 15, 2 )*1;
          self::$sekunda = substr( self::$datum, 18, 2 )*1;
          self::$den_v_tydnu = NULL;

     }
     //print_r( $this );
}

static function horoskop() {

     if( self::$den != 0 ) {

          if     ( (self::$mesic==3  && self::$den>=21) || (self::$mesic==4  && self::$den<=20) ) $znameni = 1;
          elseif ( (self::$mesic==7  && self::$den>=23) || (self::$mesic==8  && self::$den<=28) ) $znameni = 2;
          elseif ( (self::$mesic==11 && self::$den>=23) || (self::$mesic==12 && self::$den<=21) ) $znameni = 3;
          elseif ( (self::$mesic==4  && self::$den>=21) || (self::$mesic==5  && self::$den<=21) ) $znameni = 4;
          elseif ( (self::$mesic==8  && self::$den>=23) || (self::$mesic==9  && self::$den<=22) ) $znameni = 5;
          elseif ( (self::$mesic==12 && self::$den>=22) || (self::$mesic==1  && self::$den<=20) ) $znameni = 6;
          elseif ( (self::$mesic==5  && self::$den>=22) || (self::$mesic==6  && self::$den<=21) ) $znameni = 7;
          elseif ( (self::$mesic==9  && self::$den>=23) || (self::$mesic==10 && self::$den<=23) ) $znameni = 8;
          elseif ( (self::$mesic==1  && self::$den>=21) || (self::$mesic==2  && self::$den<=20) ) $znameni = 9;
          elseif ( (self::$mesic==6  && self::$den>=22) || (self::$mesic==7  && self::$den<=22) ) $znameni = 10;
          elseif ( (self::$mesic==10 && self::$den>=24) || (self::$mesic==11 && self::$den<=22) ) $znameni = 11;
          elseif ( (self::$mesic==2  && self::$den>=21) || (self::$mesic==3  && self::$den<=20) ) $znameni = 12;

          $znameni_jm = array (1=>'berana','lva','střelce','býka','panny','kozoroha','blížence','vah','vodnáře','raka','štíra','ryb');

          return $znameni = ' ve znamení '.$znameni_jm[$znameni];

     }
}


public static function narozen( $datum, $horoskop = 0, $pojmenuj = 1, $den_v_tydnu = 1, $pouzij_predlozku = 1 ) {

     self::$datum = $datum;

     self::rozdel();

     if( self::$den == 0 && self::$mesic != 0 ) self::$format_vystupu = ( $pojmenuj ? ( $pouzij_predlozku ? 'v %9$s %6$4d' : '%8$s %6$4d' ) : '%5$d. %6$4d' );
     elseif( self::$mesic == 0 ) self::$format_vystupu = ($pouzij_predlozku ? 'v roce' : 'roku').'%6$4d';
     else self::$format_vystupu = $pojmenuj ? '%4$d. %7$s %6$4d' : '%4$d. %5$d. %6$4d'; // je platný den, měsíc, rok

     if( $den_v_tydnu == 1 ) { // vrátí i název dne v týdnu

          switch( self::$den_v_tydnu ) {
            case 3:
            case 4: self::$predlozka['den_v_tydnu'] = 've'; break;
            default: self::$predlozka['den_v_tydnu'] = 'v'; break;
          }

          self::$format_vystupu = ( $pouzij_predlozku ? '%2$s ' : '').'%3$s '.self::$format_vystupu;

     }

     if( self::$hodina != '0' ) {

          switch( self::$hodina ) {
            case 2:
            case 3:
            case 4:
            case 12:
            case 13:
            case 14:
            case 20:
            case 21:
            case 22:
            case 23:
            case 24: self::$predlozka['cas'] = 've'; break;
            default: self::$predlozka['cas'] = 'v';
          }
          self::$format_vystupu .= " %9\$s %10\$d.%11\$'02d";

          if( self::$sekunda != '0' ) self::$format_vystupu .= '.%12$2s';

     }

     self::$datum = sprintf( self::$format_vystupu,
     self::$nedavno,
     self::$predlozka['den_v_tydnu'], self::$nazvy['dne_v_tydnu'][self::$den_v_tydnu],
     self::$den, self::$mesic, self::$rok,
     self::$nazvy['mesice'][self::$mesic], self::$nazvy['mesic'][self::$mesic],
     self::$predlozka['cas'], self::$hodina, self::$minuta, self::$sekunda );

     if( $horoskop == 1 ) self::$datum .= self::horoskop();

     return self::$datum;
     //return self::$dnes['den'].'. '.self::$dnes['mesic'].'. '.self::$dnes['hodina'];

}

public static function date( $datum, $pojmenuj = 1, $den_v_tydnu = 1, $pouzij_predlozku = 1 ) {

     self::$datum = $datum;
     self::$predlozka['cas'] = '';
     self::$predlozka['den_v_tydnu'] = '';
     self::$nedavno = NULL;
     self::rozdel();

     // Začne se kontrolovat předvčírem, včera, dnes, zítra a pozítří
     if( $pojmenuj == 1 ) {
         if( self::$den == self::$predvcera['den'] and self::$mesic == self::$predvcera['mesic'] and self::$rok == self::$predvcera['rok'] ) self::$nedavno = 'předevčírem';
         elseif( self::$den == self::$vcera['den'] and self::$mesic == self::$vcera['mesic'] and self::$rok == self::$vcera['rok'] ) self::$nedavno = 'včera';
         elseif( self::$den == self::$dnes['den'] and self::$mesic == self::$dnes['mesic'] and self::$rok == self::$dnes['rok'] ) self::$nedavno = 'dnes';
         elseif( self::$den == self::$zitra['den'] and self::$mesic == self::$zitra['mesic'] and self::$rok == self::$zitra['rok'] ) self::$nedavno = 'zítra';
         elseif( self::$den == self::$pozitri['den'] and self::$mesic == self::$pozitri['mesic'] and self::$rok == self::$pozitri['rok']) self::$nedavno = 'pozítří';
         else {
             self::$nedavno = NULL; // Nenastaví se nic
             //$pouzij_predlozku = false;
         }
     }

     if( self::$den == 0 && self::$mesic != 0 ) self::$format_vystupu = ( $pojmenuj ? ( $pouzij_predlozku ? 'v %9$s %6$4d' : '%8$s %6$4d' ) : '%5$d. %6$4d' );
     elseif( self::$mesic == 0 ) self::$format_vystupu = ($pouzij_predlozku ? 'v roce' : 'roku').' %6$4d';
     else self::$format_vystupu = $pojmenuj ? '%4$d. %7$s %6$4d' : '%4$d. %5$d. %6$4d'; // je platný den, měsíc, rok

     if( $den_v_tydnu == 1 && self::$den_v_tydnu != NULL ) { // vrátí i název dne v týdnu

          switch( self::$den_v_tydnu ) {
            case 3:
            case 4: self::$predlozka['den_v_tydnu'] = 've'; break;
            default: self::$predlozka['den_v_tydnu'] = 'v';
          }

          self::$format_vystupu = ( $pouzij_predlozku ? '%2$s %3$s' : self::$nazvy['den_v_tydnu'][self::$den_v_tydnu]).' '.self::$format_vystupu;

     }

     self::$den_stamp = 86400;

     if( isset( self::$nedavno ) ) self::$format_vystupu = '%1$s';

     if( self::$hodina != '0' || self::$minuta != '0' || self::$sekunda != '0' ) {

          switch( self::$hodina ) {
            case 2:
            case 3:
            case 4:
            case 12:
            case 13:
            case 14:
            case 20:
            case 21:
            case 22:
            case 23:
            case 24: self::$predlozka['cas'] = 've'; break;
            default: self::$predlozka['cas'] = 'v';
          }
          self::$format_vystupu .= " %10\$s %11\$d.%12\$'02d";

          if( self::$sekunda != '0' ) self::$format_vystupu .= ':%13$2s';

     }

     self::$datum = sprintf( self::$format_vystupu,
     self::$nedavno,
     self::$predlozka['den_v_tydnu'], self::$nazvy['dne_v_tydnu'][self::$den_v_tydnu],
     self::$den, self::$mesic, self::$rok,
     self::$nazvy['mesice'][self::$mesic], self::$nazvy['mesic'][self::$mesic], self::$nazvy['mesici'][self::$mesic],
     self::$predlozka['cas'], self::$hodina, self::$minuta, self::$sekunda );

     return self::$datum;
     //return self::$dnes['den'].'. '.self::$dnes['mesic'].'. '.self::$dnes['hodina'];
}

    public function iso_datum($zceho) {
        if( strpos( $zceho, '0000' ) || empty( $zceho ) ) return NULL;
        $datum = '(?P<den>[0-9]{1,2})([^0-9]+)(?P<mesic>[0-9]{1,2})[^0-9]+(?P<rok>[0-9]{4})';
        $cas = '([^0-9]+(?P<hodiny>[0-9]{1,2})([^0-9]+(?P<minuty>[0-9]{1,2})([^0-9]+(?P<sekundy>[0-9]{1,2}))?)?)?';
        if( preg_match('~^'.$datum.$cas.'$~', $zceho, $match) ) {}
        elseif( preg_match('~^'.$cas.$datum.'$~', $zceho, $match) ) {}
        elseif( preg_match('~^(?P<rok>[0-9]{2,4})\-(?P<mesic>[0-9]{1,2})\-(?P<den>[0-9]{1,2})'.$cas.'$~', $zceho, $match) ) {}
        else { return false; }

        if( !intval( $match['rok'] ) || !intval( $match['mesic'] ) || !intval( $match['den'] ) ) return false;

        return date( 'Y-m-d H:i:s', mktime( intval($match['hodiny']), intval($match['minuty']), intval($match['sekundy']), intval($match['mesic']), intval($match['den']), intval($match['rok']) ) );

    }

    public function od_do( $od, $do ) {
        $vypis = self::date( $od );
        if( empty( $do ) ) $do = '0000-00-00';
        if( !preg_match( '~^0000~', $od ) && !preg_match( '~^0000~', $do ) ) $vypis.= ' až ';
        if( !preg_match( '~^0000~', $do ) ) {
             if( !preg_match( '~00:00:00$~', $od ) && !preg_match( '~00:00:00$~', $do ) ) {
                  self::$datum = $od; self::rozdel(); $start_rok = self::$rok; $start_mesic = self::$mesic; $start_den = self::$den;
                  self::$datum = $do; self::rozdel(); $konec_rok = self::$rok; $konec_mesic = self::$mesic; $konec_den = self::$den;
                  if( $start_rok == $konec_rok && $start_mesic == $konec_mesic && $start_den == $konec_den ) $vypis.= self::$hodina.'.'.self::$minuta; //substr( $do, 11, -3 );
                  else $vypis.= self::date( $do );
             }
             else $vypis.= self::date( $do );
        }
        return $vypis;
    }

    public function vek( $datum_narozeni, $pojmenovat = false ) {
        $datum_narozeni = strtotime( $datum_narozeni );
        $vek = floor( (date("Ymd") - date("Ymd", $datum_narozeni)) / 10000);
        if( $vek == 0 )
        {
            $vek = strtotime("now") - strtotime( $datum_narozeni );
            $mesice = intval(date( "m", $vek ));
            if( $pojmenovat ) return $mesice.' '.functions::spravny_tvar( $mesice, "měsíc", "měsíce", "měsíců" );
            else return 0;
        }
        if( $pojmenovat )
        {
            return $vek.' '.functions::spravny_tvar( $vek, 'rok', 'roky', 'let' );
        }
        else return $vek;
    }

}
