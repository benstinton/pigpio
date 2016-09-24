

// Model class for each Muppet item
var IssueModel = Backbone.Model.extend({
  /*defaults: {
    id: null,
    number: null,
    title: null
  },*/
  urlRoot: 'https://api.github.com/repos/benstinton/amber/issues/',
  url: function() {
    return this.urlRoot + this.id + '?access_token=1d493c626f3ef3cee3bfef3407727f55495c9132';
  }
});



// Collection class for the Muppets list endpoint
var IssueCollection = Backbone.Collection.extend({
  model: IssueModel,
  urlRoot: 'https://api.github.com/repos/benstinton/amber/issues',
  url: function() {
    return this.urlRoot + '?access_token=1d493c626f3ef3cee3bfef3407727f55495c9132' + '&labels=' + Badge + '&sort=updated' + '&direction=desc';
  }/*,
  parse: function(data) {
    return data;
  }*/
  
});

//alert(JSON.stringify(muppetsCol));
//alert(IssueCollection);

// View class for displaying each muppet list item
var IssuesListItemView = Backbone.View.extend({
  tagName: 'div',
  className: 'col-md-12',
  template: _.template($('#issue-item-tmpl').html()),

  initialize: function() {
    //this.listenTo(this.model, 'destroy', this.remove);
    //this.listenTo(this.model, 'save', this.save);
  },

  render: function() {
    var html = this.template(this.model.toJSON());
    this.$el.html(html);
    return this;
  },

  events: {
    //'click .remove': 'onRemove',
    //'click .update': 'onSave'
  },

  onRemove: function() {
    this.model.destroy();
  }/*,
  onSave: function() {
    var name = this.$('#muppet-name-' + this.$('.update').data('id'));
    var job = this.$('#muppet-job-' + this.$('.update').data('id'));

    if (name.val()) {
      this.model.save({  // save syncs to storage, set just updates the model
        id: this.model.id,
        name: name.val(),
        occupation: job.val()
      });

    }
  }*/
});



// View class for rendering the list of all muppets
var IssuesListView = Backbone.View.extend({
  el: '#amber-app',

  initialize: function() {
    this.listenTo(this.collection, 'sync', this.render);
  },

  render: function() {
    //var $list = this.$('table.issues-list > tbody').empty();
    var $list = this.$('div.issues-grid').empty();

    var i = 0;
    this.collection.each(function(model) {
      var item = new IssuesListItemView({model: model});
      $list.append(item.render().$el);
      i++;
      if (i % 2 == 0){
        //$list.append('</div><div class="row">');
      }
    }, this);

    var $count = this.$('#count').empty().append(this.collection.length);
    return this;
  },

  events: {
    'click .create': 'onCreate'
  }
});

// Create a new list collection, a list view, and then fetch list data:
var Badge = $('#amber-app').data('badge');
var IssuesList = new IssueCollection(Badge);
var issuesView = new IssuesListView({collection: IssuesList});
IssuesList.fetch();