<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MlsrListingsMaster extends Model  {

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'mysql_mlsr';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'mlsr_listings';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['sysid', 'listingid', 'status', 'listingtype', 'reciprocity', 'postalcode', 'postalarea', 'streetaddress', 'province', 'board', 'muncipality', 'yearbuilt', 'virtualtoururl', 'suite_no', 'street_dir', 'street_number', 'last_modified', 'list_date', 'listprice', 'listprice_2', 'street_name', 'street_type', 'pid', 'bedrooms', 'headline', 'remarks', 'basement', 'exposure', 'frontage', 'depth', 'kitchens', 'bath1_ensuite', 'bath1_pieces', 'bath2_ensuite', 'bath2_pieces', 'bath3_ensuite', 'bath3_pieces', 'bath4_ensuite', 'bath4_pieces', 'bath5_ensuite', 'bath5_pieces', 'construction', 'parking_total_covered', 'room1_dim1', 'room2_dim1', 'room3_dim1', 'room4_dim1', 'room5_dim1', 'room6_dim1', 'room7_dim1', 'room8_dim1', 'room9_dim1', 'room10_dim1', 'room11_dim1', 'room12_dim1', 'room13_dim1', 'room14_dim1', 'room15_dim1', 'room16_dim1', 'room17_dim1', 'room18_dim1', 'room19_dim1', 'room20_dim1', 'room1_dim2', 'room2_dim2', 'room3_dim2', 'room4_dim2', 'room5_dim2', 'room6_dim2', 'room7_dim2', 'room8_dim2', 'room9_dim2', 'room10_dim2', 'room11_dim2', 'room12_dim2', 'room13_dim2', 'room14_dim2', 'room15_dim2', 'room16_dim2', 'room17_dim2', 'room18_dim2', 'room19_dim2', 'room20_dim2', 'full_baths', 'fireplaces', 'floor_finish', 'foundation', 'half_baths', 'room1_level', 'room2_level', 'room3_level', 'room4_level', 'room5_level', 'room6_level', 'room7_level', 'room8_level', 'room9_level', 'room10_level', 'room11_level', 'room12_level', 'room13_level', 'room14_level', 'room15_level', 'room16_level', 'room17_level', 'room18_level', 'room19_level', 'room20_level', 'room21_level', 'room22_level', 'room23_level', 'room24_level', 'room25_level', 'room26_level', 'room27_level', 'room28_level', 'finished_levels', 'maintenance', 'occupancy', 'outdoor_area', 'property_disclosure', 'parking', 'roof', 'dist_school_bus', 'dist_trans', 'water_supply', 'exterior_finish', 'heating', 'fireplace_details', 'features', 'amenity', 'maint_fees_inc', 'reno_year', 'bath1_level', 'bath2_level', 'bath3_level', 'bath4_level', 'bath5_level', 'bath6_pieces', 'bath6_ensuite', 'bath6_level', 'bath7_pieces', 'bath7_ensuite', 'bath7_level', 'bath8_pieces', 'bath8_ensuite', 'bath8_level', 'area', 'bylaw_restrictions', 'rear_yard_exposure', 'legal_description', 'lotsize', 'lotsize_sqmtrs', 'room1_type', 'room2_type', 'room3_type', 'room4_type', 'room5_type', 'room6_type', 'room7_type', 'room8_type', 'room9_type', 'room10_type', 'room11_type', 'room12_type', 'room13_type', 'room14_type', 'room15_type', 'room16_type', 'room17_type', 'room18_type', 'room19_type', 'room20_type', 'room21_type', 'room22_type', 'room23_type', 'room24_type', 'room25_type', 'room26_type', 'room27_type', 'room28_type', 'subarea', 'room21_dim1', 'room21_dim2', 'room22_dim1', 'room22_dim2', 'room23_dim1', 'room23_dim2', 'room24_dim1', 'room24_dim2', 'room25_dim1', 'room25_dim2', 'room26_dim1', 'room26_dim2', 'room27_dim1', 'room27_dim2', 'room28_dim1', 'room28_dim2', 'taxyear', 'taxamount', 'reoffice', 'reoffice2', 'reoffice3', 'reoffice_url', 'reoffice2_url', 'reoffice_phone', 'reoffice2_phone', 'agent_email', 'agent_name', 'agent_phone', 'agent_url', 'agent_id', 'agent2_email', 'agent2_name', 'agent2_phone', 'agent2_url', 'agent2_id', 'agent3_email', 'agent3_name', 'agent3_phone', 'agent3_url', 'agent3_id', 'home_style', 'title_to_land', 'possession', 'parking_access', 'sellers_interest', 'parking_stall_owned', 'legal_description2', 'city', 'livingarea', 'livingarea_2', 'parking_total', 'site_influences', 'bathstotal', 'units_in_development', 'units_in_strata', 'additionalphotoxml', 'mainpicurl', 'thumbnailurl', 'main_photo_id', 'left_photo_id', 'middle_photo_id', 'right_photo_id', 'class', 'geoCode', 'lat', 'lng', 'geoAccuracy', 'geoType', 'building_id', 'internal_building_id', 'displayAsListing', 'assignment', 'lastImgTrans', 'updated', 'inserted', 'floorplan', 'displayOnInternet', 'displayAddress', 'complex', 'mgmt_name', 'mgmt_phone', 'no_pets', 'levels', 'cats', 'dogs', 'strata_no', 'rain_screen', 'restricted_age', 'view', 'locker', 'youtube_video_id', 'virtualtour_iframe', 'virtualtour_swf', 'virtualtour_width', 'virtualtour_height', 'videotour_flv', 'videotour_swf', 'videotour_thumbnail', 'videotour_width', 'videotour_height', 'virtualtour_data', 'pendingUpdate', 'local_version', 'remote_version', 'fusionRowID', 'slug', 'exclusive', 'open_house', 'expiration_date', 'original_price', 'sold_date', 'soldprice', 'soldprice_2', 'dom', 'cdom', 'region', 'tax_util_incl', 'garage_size', 'door_height', 'barn_size', 'shed_size', 'pool_size', 'prop_disc', 'gst_incl', 'legal_desc', 'commission', 'prev_price', 'notax_incl_grbg', 'notax_incl_water', 'notax_incl_dyking', 'notax_incl_sewer', 'notax_incl_other', 'soldoffice_short1', 'soldoffice1', 'soldoffice_short2', 'soldoffice2', 'soldoffice_short3', 'soldoffice3', 'bylaw_infr', 'perc_test_date', 'bldg_permit_appr', 'info_pckg_appr', 'dev_permit', 'perm_land_use', 'prop_in_lnd_res', 'prospectus', 'bldg_plans', 'perc_test_avail', 'sign_on_prop', 'front_dir_exp'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['last_modified', 'list_date', 'updated', 'inserted', 'expiration_date', 'sold_date', 'perc_test_date'];

    public function listing_master(){
        return $this->belongsTo('App\Models\Listings', 'sysid', 'sysid');
    }

}
