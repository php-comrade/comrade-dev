import {Component, EventEmitter, Output} from '@angular/core';
import {JobTemplate} from "../shared/job-template";
import 'rxjs/add/operator/switchMap';
import 'rxjs/add/operator/do';
import 'rxjs/add/operator/map';
import {HttpService} from "../shared/http.service";
import {Response} from "@angular/http";
import {Subject} from "rxjs/Subject";
import {CompleterData, CompleterItem} from "ng2-completer";
import {SearchTemplates} from "../shared/messages/search-templates";
import {SearchTemplatesResult} from "../shared/messages/search-templates-result";

class JobTemplateCompleter extends Subject<CompleterItem[]> implements CompleterData {
  constructor(private httpService: HttpService) {
    super();
  }
  public search(term: string): void {
    let query = new SearchTemplates();
    query.term = term;
    query.limit = 5;

    this.httpService.post('/api/search-templates', query)
      .map((res: Response) => {
        // Convert the result to CompleterItem[]
        let result = res.json() as SearchTemplatesResult;

        if (result.templates) {
          let items: CompleterItem[] = result.templates.map((template: JobTemplate) => this.convertToItem(template));

          this.next(items);
        } else {
          this.next(null);
        }
      })
      .subscribe();
  }

  public cancel() {
    // Handle cancel
  }

  public convertToItem(template: JobTemplate): CompleterItem | null {
    if (!template) {
      return null;
    }

    return {
      title: template.name,
      originalObject: template,
    } as CompleterItem;
  }
}

@Component({
  selector: 'template-search',
  template: `<ng2-completer (selected)="selected($event)" [datasource]="this.completer" [minSearchLength]="2"></ng2-completer>`
})
export class TemplateSearchComponent {
  @Output() onSelected = new EventEmitter<JobTemplate>();
  @Output() onUnSelected = new EventEmitter<void>();

  completer: JobTemplateCompleter;

  constructor(private httpService: HttpService) {
    this.completer = new JobTemplateCompleter(this.httpService);
  }

  public selected(item: CompleterItem): void
  {
    if (item) {
      this.onSelected.emit(item.originalObject);
    } else {
      this.onUnSelected.emit();
    }
  }
}
