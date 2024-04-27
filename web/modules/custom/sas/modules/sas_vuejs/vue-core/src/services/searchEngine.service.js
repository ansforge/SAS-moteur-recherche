import axios from 'axios';
import { ApiPlugin } from '@/plugins';
import {
  SAS_SUGGESTIONS,
  SAS_SEARCH_TEXT_SUGGESTIONS,
} from '@/const/api.const';

export default class SearchEngine {
  static async getSearchSuggestions() {
    try {
      const response = await ApiPlugin.get(SAS_SUGGESTIONS);
      return response && response.data;
    } catch (e) {
      console.error('Error fetching SuggestionSearch \n', e);
      return [];
    }
  }

  static async getSearchSuggestionsByText(searchText, abortSignal) {
    try {
      const response = await ApiPlugin.get(
      `${SAS_SEARCH_TEXT_SUGGESTIONS}/${searchText}`,
      { signal: abortSignal },
    );
      return response && response.data;
    } catch (e) {
      if (!axios.isCancel(e)) {
        console.error('Error fetching SuggestionSearchText \n', e);
      }

      return [];
    }
  }
}
