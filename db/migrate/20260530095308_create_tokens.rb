class CreateTokens < ActiveRecord::Migration[8.0]
  def change
    create_table :tokens do |t|
      t.timestamps

      t.string :value, null: false
      t.datetime :expires_at, null: false
      t.references :account, null: false, foreign_key: true
    end
  end
end
