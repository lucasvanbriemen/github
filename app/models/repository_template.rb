# Issue/PR body templates, loaded from config/repository_templates.json.
# Not a database table — a small read-only loader over the JSON file.
class RepositoryTemplate
  PATH = Rails.root.join("config/repository_templates.json")

  Template = Struct.new(:id, :type, :name, :body, keyword_init: true)

  def self.all
    @all ||= JSON.parse(PATH.read).map do |data|
      Template.new(id: data["id"], type: data["type"], name: data["name"], body: data["body"])
    end
  end

  # kind is "issue" or "pr".
  def self.for(kind)
    all.select { |template| template.type == kind }
  end
end
