<?php
// Headings
$_['heading_title']        	   = 'Nastavitve';
$_['text_openbay']             = 'OpenBay Pro';
$_['text_fba']                 = 'Izpolnitev Amazon';

// Text
$_['text_success']     		   = 'Nastavitve so bile shranjene';
$_['text_status']         	   = 'Stanje';
$_['text_account_ok']  		   = 'Povezava z izpolnjevanjem Amazon ok';
$_['text_api_ok']       	   = 'API povezava ok';
$_['text_api_status']          = 'API povezava';
$_['text_edit']           	   = 'Edit izpolnitev z nastavitvami Amazon';
$_['text_standard']            = 'Standardni';
$_['text_expedited']           = 'Hitrem';
$_['text_priority']            = 'Prednost';
$_['text_fillorkill']          = 'Napolni ali ubij';
$_['text_fillall']             = 'Izpolnite vse';
$_['text_fillallavailable']    = 'Izpolnite vse razpoložljive';
$_['text_prefix_warning']      = 'Te nastavitve ne spreminjajte, ko so naročila poslana Amazonu, to nastavite šele, ko prvič namestite.';
$_['text_disabled_cancel']     = 'Onemogočeno-samodejno ne Prekliči izpolnitve';
$_['text_validate_success']    = 'Vaš API podrobnosti delujejo pravilno! Če želite zagotoviti, da so nastavitve shranjene, morate pritisniti shrani.';
$_['text_register_banner']     = 'Kliknite tukaj, če se morate registrirati za račun';

// Entry
$_['entry_api_key']            = 'API key';
$_['entry_encryption_key']     = 'Šifrirni ključ 1';
$_['entry_encryption_iv']      = 'Šifrirni ključ 2';
$_['entry_account_id']         = 'Račun nagonski podnet posameznika';
$_['entry_send_orders']        = 'Samodejno pošiljanje nalogov';
$_['entry_fulfill_policy']     = 'Izpolnitev politike';
$_['entry_shipping_speed']     = 'Privzeta hitrost pošiljanja';
$_['entry_debug_log']          = 'Omogoči odpravljanje napak pri prijavi';
$_['entry_new_order_status']   = 'Nov sprožilec izpolnitve';
$_['entry_order_id_prefix']    = 'Predpona ID naročila';
$_['entry_only_fill_complete'] = 'Vsi artikli morajo biti FBA';

// Help
$_['help_api_key']             = 'This is your API key, obtain this from your OpenBay Pro account area';
$_['help_encryption_key']      = 'To je vaš šifrirni ključ #1, pridobiti to iz vašega OpenBay Pro račun area';
$_['help_encryption_iv']       = 'To je vaš šifrirni ključ #2, pridobiti to iz vašega OpenBay Pro račun area';
$_['help_account_id']          = 'To je račun nagonski podnet posameznika to vžigalica priproročeno presenetiti račun zakaj OpenBay zagovornik predloga, doseči ceno to s vaš OpenBay zagovornik predloga račun area';
$_['help_send_orders']  	   = 'Naročila, ki vsebujejo ujemanje izpolnitev s Amazon izdelki bodo poslali na Amazon samodejno';
$_['help_fulfill_policy']  	   = 'Privzeta politika izpolnitve (FillAll-vse izpolnitvene postavke v nalogu za izpolnitev so dobavljene. Nalog za izpolnitev ostane v stanju obdelave, dokler ga prodajalec ne pošlje vsem artiklom ali pa jih prekliče Amazon. FillAllAvailable-vse izpolnjene postavke v izpolnitev nalog se pošiljajo. Vse neizpolnjene postavke v vrstnem redu, ki jih prekliče Amazon. FillOrKill-če je določen element v izpolnitvenem nalogu neizpolnjen pred vsakim pošiljanjem v nalogu, se preseli v stanje, v katerem je prišlo do nerešenega stanja (postopek komisioniranja enot iz zaloge), nato pa celotno naročilo se šteje za neizpolnjeno. Če pa je element v izpolnitvenem nalogu določen za neizpolnjevanje po dobavnici v nalogu, ki se premakne v stanje čakajočega stanja, Amazon prekliče čim večji vrstni red izpolnitve.)';
$_['help_shipping_speed']  	   = 'To je privzeta Kategorija hitrosti pošiljanja, ki se uporablja za nova naročila, lahko različne ravni storitev nastanejo različni stroški';
$_['help_debug_log']  		   = 'Debug dnevniki bo zapis informacij v dnevniško datoteko o dejanjih modul ne. To mora ostati omogočeno, da pomaga najti vzrok za kakršne koli težave.';
$_['help_new_order_status']    = 'To je stanje reda, ki bo sprožilo vrstni red, ki bo ustvarjen za izpolnitev. Prepričajte se, da to uporablja stanje šele potem, ko ste prejeli plačilo.';
$_['help_order_id_prefix']     = 'Z predpono naročila boste lažje identificirali naročila, ki prihajajo iz vaše trgovine, ne iz drugih integracij. To je zelo koristno, ko trgovci prodajajo na številnih tržnicah in uporabo FBA';
$_['help_only_fill_complete']  = 'To bo omogočilo samo naročila, ki jih je treba poslati za izpolnitev, če so vsi elementi v vrstnem redu, ki se ujemajo z izpolnjevanjem, ki jih element Amazon. Če kateri koli element ni potem celotno naročilo bo ostalo nenapolnjeno.';

// Error
$_['error_api_connect']        = 'Povezava z API-jem ni uspela';
$_['error_account_info']       = 'Nezmožen v preveriti API vez v izpolnitev z presenetiti ';
$_['error_api_key']    		   = 'The API key is invalid';
$_['error_api_account_id']     = 'ID računa ni veljaven';
$_['error_encryption_key']     = 'Šifrirni ključ #1 ni veljaven';
$_['error_encryption_iv']      = 'Šifrirni ključ #2 ni veljaven';
$_['error_validation']    	   = 'Prišlo je do napake pri potrjevanju vaših podatkov';

// Tabs
$_['tab_api_info']             = 'Podrobnosti API';

// Buttons
$_['button_verify']            = 'Preverite podrobnosti';
