# vCardGenerator
a simple php vCard Generator
forked from @dwieeb

## Getting Started

simply copy the class include it:
`include_once('..vCardGenerator.php');`

# Usage example

    function view($data) {
    

    require_once('models/' . 'vCardGenerator.php');
    
    $vcard = new vCardGenerator();
    
    // set card parameters
    $vcard->setFullName($data['card']['cname']);
    $vcard->setOrganization($data['card']['company']);
    $vcard->setTitle($data['card']['title']);
    $vcard->setImageUrl(CARD_BASE_URL . $data['card']['image_url'],'PNG');
    $it_exceptions = array('address' => 'address', 'site' => 'url');
    foreach ($data['card']['items'] as $item) {
        $item['itemtype'] =  (in_array($item['item_type_name'],$it_exceptions)) ? $it_exceptions[$item['item_type_name']] : $item['item_type_value'];
    //	$item['itemtype'] = ($item['item_type_name'] == 'address') ? 'address' : $item['item_type_value'];
    	$item['servicetype'] = $item['item_type_name'];
    	$item['type'] = $item['item_group_name'];

 	try {
    	$vcard->addItem($item);
	} catch(Exception $e) {
	  //echo "Message: " .$e->getMessage() . "\n\r" . "value: " . $item['value'] ;
	}
   
    }
	 


    return $vcard->generateVCard();
    
    }
