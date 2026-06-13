class AddBannerToOrganizations < ActiveRecord::Migration[8.0]
  def change
    change_table :organizations do |t|
      t.string :banner_light_url, null: true
      t.string :banner_dark_url, null: true
    end
  end
end
