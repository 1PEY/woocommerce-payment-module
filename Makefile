SHELL=/bin/bash
all:
	if [[ -e woocommerce-onepey.zip ]]; then rm woocommerce-onepey.zip; fi
	zip -r woocommerce-onepey.zip woocommerce-onepey -x "*/test/*" -x "*/.git/*" -x "*/examples/*" -x "*.git*" -x "*.project*" -x "*.travis*" -x "*.build*" 
