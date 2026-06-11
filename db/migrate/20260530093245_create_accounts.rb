class CreateAccounts < ActiveRecord::Migration[8.0]
  def change
    create_table :accounts do |t|
      t.timestamps
      t.string :email, null: false
      t.string :password_digest, null: false
      t.string :name, null: false

      t.index :email, unique: true
    end
  end
end
